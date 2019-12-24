<?php

namespace Drupal\jcc_judges_in_the_classroom\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
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
      '#description' => $this->t('Searches for a match with @profile', ['@profile' => $this->configuration['match_with']]),
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
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   *
   * @return array
   *   Boolean.
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
   *   Webform submission.
   *
   * @return array
   *   Matches.
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
        ],
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
            $data['county'],
            $date['preferred_day'],
            $hour,
            $this->configuration['match_with']
          );
          if ($profiles) {
            foreach ($profiles as $match) {
              $matches[$data['county']][$date['preferred_day']][$hour][] = $match;
            }
          }
        }
      }
    }

    return $matches;
  }

  /**
   * Add matches to the message body.
   *
   * @param array $message
   *   Message.
   * @param array $matches
   *   Matches.
   *
   * @return array
   *   Message.
   */
  protected function addMatchesToMessage(array $message, array $matches) {

    $count = 0;
    $table_matches = [];
    $header_matches = [];
    $rows_matches = [];
    foreach ($matches as $county => $days) {
      foreach ($days as $day => $hours) {
        foreach ($hours as $hour => $profiles) {
          $count++;

          if ($this->configuration['match_with'] == JudgesInTheClassroom::JUDGE) {
            $header_matches = [
              'Court Name',
              'Day',
              'Hour',
            ];
          }
          else {
            $header_matches = [
              'Visit Date',
              'School',
              'Teacher',
            ];
          }

          foreach ($profiles as $profile) {
            if ($this->configuration['match_with'] == JudgesInTheClassroom::JUDGE) {
              $rows_matches[] = [
                '#markup' => [
                  $profile['court_name'],
                  $profile['day'],
                  $profile['hour'],
                ],
                '#wrapper_attributes' => [
                  'style' => ['border: 2px dashed #a0a0a0;'],
                  'cellpadding' => 2,
                ],
              ];
            }
            else {
              $rows_matches[] = [
                '#markup' => [
                  date('M d, Y h:iA', strtotime($profile['preferred_date'])),
                  $profile['school_name'],
                  $profile['teacher_name'] . '<br />' .
                  $profile['email'] . '<br />' .
                  $profile['phone'],
                ],
                '#wrapper_attributes' => [
                  'style' => ['border: 2px dashed #a0a0a0;'],
                  'cellpadding' => 2,
                ],
              ];
            }
          }
          $table_matches = [
            '#theme' => 'table',
            '#header' => $header_matches,
            '#rows' => $rows_matches,
          ];
        }
      }
    }

    $text = '<h2>';
    $text .= $this->formatPlural($count, 'One match found.', '@count matches found!');
    $text .= '</h2>';
    $text .= render($table_matches);
    $message['body'] = str_replace('[matches]', $text, $message['body']);

    return $message;
  }

}
