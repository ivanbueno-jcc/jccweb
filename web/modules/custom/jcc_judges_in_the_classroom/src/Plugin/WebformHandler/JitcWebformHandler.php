<?php

namespace Drupal\jcc_judges_in_the_classroom\Plugin\WebformHandler;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jcc_judges_in_the_classroom\Service\JudgesInTheClassroom;

/**
 * Judges in the Classroom - Profile matching.
 *
 * @WebformHandler(
 *   id = "jcc_jitc",
 *   label = @Translation("Judges in the Classroom - Profile Matching"),
 *   category = @Translation("Judicial Council"),
 *   description = @Translation("Sends an email to judges and teachers if a match is found."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class JitcWebformHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $webform_settings = $this->configFactory->get('webform.settings');

    return [
      'match_with' => JudgesInTheClassroom::JUDGE,
      'from_email' => \Drupal::config('system.site')->get('mail'),
      'from_name' => '',
      'subject' => $webform_settings->get('mail.default_subject') ?: 'Judges in the Classroom - Match found!',
      'body' => $webform_settings->get('mail.default_body_html') ?: '[webform_submission:values]',
      'html' => TRUE,
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Message.
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

    $form['message'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Message'),
    ];
    $form['message']['from_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t("From Email"),
      '#default_value' => $this->configuration['from_email'],
    ];
    $form['message']['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("From Name"),
      '#default_value' => $this->configuration['from_name'],
    ];
    $form['message']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Subject"),
      '#default_value' => $this->configuration['subject'],
    ];
    $form['message']['body'] = [
      '#type' => 'webform_html_editor',
      '#format' => $this->configFactory->get('webform.settings')->get('html_editor.mail_format'),
      '#title' => $this->t('Body'),
      '#title_display' => 'hidden',
      '#default_value' => $this->configuration['body'],
    ];
    // Tokens.
    $form['message']['token_tree_link'] = $this->buildTokenTreeElement();

    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    $form['additional']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email as HTML'),
      '#return_value' => TRUE,
      '#access' => $this->supportsHtml(),
      '#default_value' => $this->configuration['html'],
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();

    foreach ($this->configuration as $name => $value) {
      $this->configuration[$name] = $values[$name];
    }

    // Cast debug.
    $this->configuration['debug'] = (bool) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
    if ($value = $form_state->getValue('element')) {
      $form_state->setErrorByName('element', $this->t('The element must be empty. You entered %value.', ['%value' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->debug(__FUNCTION__, $update ? 'update' : 'insert');

    $message = $this->findMatches($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function debug($method_name, $context1 = NULL) {
    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTokenTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL) {
    $description = $description ?: $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.');
    return parent::buildTokenTreeElement($token_types, $description);
  }

  /**
   * Check that HTML emails are supported.
   *
   * @return bool
   *   TRUE if HTML email is supported.
   */
  protected function supportsHtml() {
    return TRUE;
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

    dsm($matches);


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
   * {@inheritdoc}
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {

//
//    (
//      [match_with] => teacher
//    [debug] =>
//    [message] =>
//    [send_from] =>
//    [from_email] => ivan.bueno@jud.ca.gov
//    [from_name] => test
//    [subject] => test
//    [body] => test
//    [html] => 1
//)


    $token_options = [
      'email' => TRUE,
      'html' => ($this->configuration['html'] && $this->supportsHtml()),
    ];

    $token_data = [];

    $message = [];

    // Copy configuration to $message.
    foreach ($this->configuration as $configuration_key => $configuration_value) {

      // Set email addresses.
      $emails = $this->getMessageEmails($webform_submission, $configuration_value);


        // Clear tokens from email values.
        $token_options['clear'] = (strpos($configuration_key, '_mail') !== FALSE) ? TRUE : FALSE;

        // Get replace token values.
        $token_value = $this->replaceTokens($configuration_value, $webform_submission, $token_data, $token_options);

        // Decode entities for all message values except the HTML message body.
        if (!empty($token_value) && is_string($token_value) && !($token_options['html'] && $configuration_key === 'body')) {
          $token_value = Html::decodeEntities($token_value);
        }

        $message[$configuration_key] = $token_value;
    }

    // Trim the message body.
    $message['body'] = trim($message['body']);

    // Convert message body to HTML.
    if ($this->configuration['html'] && $this->supportsHtml()) {
      // Apply optional global format to body.
      // NOTE: $message['body'] is not passed-thru Xss::filter() to allow
      // style tags to be supported.
      if ($format = $this->configFactory->get('webform.settings')->get('html_editor.mail_format')) {
        $build = [
          '#type' => 'processed_text',
          '#text' => $message['body'],
          '#format' => $format,
        ];
        $message['body'] = $this->themeManager->renderPlain($build);
      }
    }

    // Add webform submission.
    $message['webform_submission'] = $webform_submission;

    // Add handler.
    $message['handler'] = $this;

    return $message;
  }
}

