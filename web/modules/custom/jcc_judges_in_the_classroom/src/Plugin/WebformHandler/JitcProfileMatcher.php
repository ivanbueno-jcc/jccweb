<?php

namespace Drupal\jcc_judges_in_the_classroom\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\jcc_judges_in_the_classroom\Service\JudgesInTheClassroom;

/**
 * Finds a match and notifies the submitter.
 *
 * @WebformHandler(
 *   id = "jitc_profile_matcher",
 *   label = @Translation("JITC Profile Matcher"),
 *   category = @Translation("Judicial Council"),
 *   description = @Translation("Sends an email to judges and teachers if a match is found."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class JitcProfileMatcher extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'match_with' => JudgesInTheClassroom::JUDGE,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();
    $summary['#status'] = [
      '#type' => 'details',
      '#title' => $this->t('Judges in the Classroom'),
      '#help' => FALSE,
      '#description' => $this->t('Searches for a match with @profile'. ['@profile' => $this->configuration['match_with']]),
    ];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['jitc'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Profile Match Settings'),
    ];
    $form['jitc']['match_with'] = [
      '#type' => 'select',
      '#title' => $this->t('Select which type to match with'),
      '#options' => [
        JudgesInTheClassroom::JUDGE => $this->t("Judges"),
        JudgesInTheClassroom::TEACHER => $this->t("Teachers"),
      ],
      '#default_value' => $this->configuration['match_with'],
      '#required' => TRUE,
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    // Change 'Send email' to 'Scheduled email'.
    $form['settings']['states']['#title'] = $this->t('JITC Profile Matching');

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $messages = $this->sendMessages($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
  }

  /**
   * Send messages to matched profiles.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *
   * @return array
   */
  public function sendMessages(WebformSubmissionInterface $webform_submission) {
    $matches = $this->findMatches($webform_submission);
    if (!empty($matches)) {

      // Get message to make sure there is a destination.
      $message = $this->getMessage($webform_submission);
      $message = $this->addMatchesToMessage($message, $matches);

      // Don't send the message if empty (aka To, CC, and BCC is empty).
      if (!$this->hasRecipient($webform_submission, $message)) {
        if ($this->configuration['debug']) {
          $this->messenger()->addWarning($this->t('%submission: Email <b>not sent</b> for %handler handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.', $t_args));
        }
        return FALSE;
      }

      $this->sendMessage($webform_submission, $message);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Find matches between judges and teacher.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *
   * @return array
   */
  public function findMatches(WebformSubmissionInterface $webform_submission) {
    $matches = [];
    $data = $webform_submission->getData();

    /* @var \Drupal\jcc_judges_in_the_classroom\Service\JudgesInTheClassroom $jitc */
    $jitc = \Drupal::service('jcc.jitc');
    $jitc->setFilters(
      [
        'counties' => [
          $data['county'] => $data['county'],
        ]
      ]
    );

    foreach ($data['preferred_hours_and_days'] as $date) {
      foreach (
        [
          $date['preferred_time'],
          $date['alternative_time'],
          $date['optional_time'],
        ] as $time) {
        if (!empty($time)) {
          $hour = substr($time, 0, 2);
          $profiles = $jitc->getMatches(
            $this->configuration['match_with'],
            $data['county'],
            $date['preferred_day'],
            $hour
          );
          if ($profiles) {
            foreach ($profiles as $match) {
              $matches[$data['county']][$date['preferred_day']][$hour][] = $match;
            }
          }
        }
      }
    }

    /**
     *     [data] => Array
    (
    [name] => Oratione
    [email] => random@random.com
    [county] => Trinity
    [court_name] => Dixisset
    [courthouse_address] => 11 Brook Alley Road. APT 1
    [preferred_group] => Array
    (
    [0] => Elementary
    [1] => Middle
    [2] => High School
    )

    [preferred_hours_and_days] => Array
    (
    [0] => Array
    (
    [preferred_day] => Friday
    [preferred_time] => 17:00:00
    [alternative_time] => 17:00:00
    [optional_time] => 09:00:00
    )

    [1] => Array
    (
    [preferred_day] => Monday
    [preferred_time] => 17:00:00
    [alternative_time] => 09:00:00
    [optional_time] => 17:00:00
    )

    [2] => Array
    (
    [preferred_day] => Thursday
    [preferred_time] => 17:00:00
    [alternative_time] => 17:00:00
    [optional_time] => 17:00:00
    )

    )

    [additional_expertise] => Lorem ipsum dolor sit amet, consectetur adipiscing elit. Negat esse eam, inquit, propter se expetendam. Primum Theophrasti, Strato, physicum se voluit; Id mihi magnum videtur. Itaque mihi non satis videmini considerare quod iter sit naturae quaeque progressio. Quare hoc videndum est, possitne nobis hoc ratio philosophorum dare. Est enim tanti philosophi tamque nobilis audacter sua decreta defendere.
    [additional_questions_or_comments] => Quae cum dixisset, finem ille. Quamquam non negatis nos intellegere quid sit voluptas, sed quid ille dicat. Progredientibus autem aetatibus sensim tardeve potius quasi nosmet ipsos cognoscimus. Gloriosa ostentatio in constituendo summo bono. Qui-vere falsone, quaerere mittimus-dicitur oculis se privasse; Duarum enim vitarum nobis erunt instituta capienda. Comprehensum, quod cognitum non habet? Qui enim existimabit posse se miserum esse beatus non erit. Causa autem fuit huc veniendi ut quosdam hinc libros promerem. Nunc omni virtuti vitium contrario nomine opponitur.
    )
     */

    return $matches;
  }

  /**
   * Add matches to the message body.
   *
   * @param array $message
   * @param array $matches
   *
   * @return array
   */
  protected function AddMatchesToMessage(array $message, array $matches) {

    $count = 0;
    $text_matches = [];
    foreach ($matches as $county => $days) {
      foreach ($days as $day => $hours) {
        foreach ($hours as $hour => $profiles)
          $count++;

          if ($this->configuration['match_with'] == JudgesInTheClassroom::JUDGE) {
            $text_matches[] = $this->formatPlural(count($profiles),
              'A judge is available on :day at :hour',
              '@count judges are available on :day at :hour',
              [
                'day' => $day,
                'hour' => $hour,
              ]);
          }
          else {
            foreach ($profiles as $profile) {
              $text_matches[] = date('m d Y. H:i s', $profile['preferred_date']) . ' - ' . $profile['teacher_name'] . ' - ' . $profile['school_name'];
            }
          }
      }
    }

    $text = '<h2>';
    $text .= $this->formatPlural($count, 'One match found.', '@count matches found!');
    $text .= '</h2>';
    $text .= '<ul>';
    $text .= implode('</li><li>', $text_matches);
    $text .= '</ul>';
    $message['body'] .= $text;

    return $message;
  }

}
