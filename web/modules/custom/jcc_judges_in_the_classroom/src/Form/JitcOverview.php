<?php

namespace Drupal\jcc_judges_in_the_classroom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\jcc_judges_in_the_classroom\Service\JudgesInTheClassroom;

/**
 * Class JitcOverview.
 *
 * @package Drupal\jcc_judges_in_the_classroom\Form
 */
class JitcOverview extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jitc_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\jcc_judges_in_the_classroom\Service\JudgesInTheClassroom $judges_in_the_classroom */
    $judges_in_the_classroom = \Drupal::service('jcc.jitc');

    $visits = $judges_in_the_classroom->getTeachers();

    $counties_rows = [];

    foreach ($visits as $county => $days) {
      $teacher_name = '';
      $school_name = '';
      $request_rows = [];

      foreach ($days as $day => $hours) {
        $option_rows = [];

        foreach ($hours as $hour => $teachers) {

          foreach ($teachers as $teacher) {
            $teacher_name = $teacher['teacher_name'];
            $school_name = $teacher['school_name'];
            $date = new DrupalDateTime($teacher['preferred_date']);

            $match_rows = [];
            $judge_matches = $judges_in_the_classroom->getMatches($county, $day, $hour, JudgesInTheClassroom::JUDGE);
            if ($judge_matches) {
              foreach ($judge_matches as $match) {
                $match_rows[] = [
                  $match['court_name'],
                  $match['name'],
                  $match['email'],
                ];
              }
            }
            $match_rows_output = [
              '#theme' => 'table',
              '#header' => ['Court', 'Judge', 'Email'],
              '#rows' => $match_rows,
            ];

            $option_rows[] = [
              $date->format('F j, Y'),
              $date->format('l'),
              $date->format('hA'),
              !empty($match_rows) ? render($match_rows_output) : 'No exact matches found.',
            ];
          }
        }

        $option_rows_output = [
          '#theme' => 'table',
          '#header' => ['Date', 'Day', 'Hour', 'Available Judges'],
          '#rows' => $option_rows,
        ];

        $request_rows[] = [
          t(":teacher <em>(:school)</em>", [
            ':teacher' => $teacher_name,
            ':school' => $school_name,
          ]),
          render($option_rows_output),
        ];
      }

      $request_rows_output = [
        '#theme' => 'table',
        '#header' => ['Teacher', 'Preferred Dates'],
        '#rows' => $request_rows,
      ];

      $counties_rows[] = [
        $county,
        render($request_rows_output),
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => ['County', 'Requests'],
      '#rows' => $counties_rows,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
