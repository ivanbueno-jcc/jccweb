<?php

namespace Drupal\jcc_judges_in_the_classroom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

    $visits = $judges_in_the_classroom->getRaw(JudgesInTheClassroom::TEACHER);
    $visit_formatted = [];

    foreach ($visits as $visit) {
      $timestamp = strtotime($visit['preferred_date']);
      $date = date('M d, Y, l', $timestamp);
      $hour = date('h:ia', $timestamp);
      $visit_formatted[$date][$hour][] = $visit;
    }

    $rows_visit = [];
    foreach ($visit_formatted as $d => $hs) {

      $rows_visit[] = [
        [
          'data' => $d,
          'colspan' => 3,
          'class' => 'font-serif-xl',
          'style' => 'border: 0px solid #ccc;',
        ],
      ];

      foreach ($hs as $h => $visits) {

        $rows_visit[] = [
          [
            'data' => $h,
            'colspan' => 3,
            'class' => 'font-serif-lg',
            'style' => 'border: 0px solid #ccc;',
          ],
        ];

        $rows_visit[] = [
          [
            'style' => 'border: 0px solid #ccc;',
          ],
          [
            'data' => 'Teacher',
            'class' => 'font-serif-sm',
            'style' => 'background-color: #f9f9f9; border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
            'width' => '30%',
          ],
          [
            'data' => 'Judges',
            'class' => 'font-serif-sm',
            'style' => 'background-color: #f9f9f9; border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
          ],
        ];

        foreach ($visits as $visit) {

          $match_rows = [];
          $judge_matches = $judges_in_the_classroom->getMatches($visit['county'], $visit['day'], $visit['hour'], JudgesInTheClassroom::JUDGE);
          if ($judge_matches) {
            foreach ($judge_matches as $match) {
              $match_rows[] = [
                [
                  'data' => $match['court_name'],
                  'style' => 'border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                  'class' => 'font-sans-2xs',
                ],
                [
                  'data' => $match['name'],
                  'style' => 'border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                  'class' => 'font-sans-2xs',
                ],
                [
                  'data' => $match['email'],
                  'style' => 'border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                  'class' => 'font-sans-2xs',
                ],
              ];
            }
          }
          $match_rows_output = [
            '#theme' => 'table',
            '#header' => [
              [
                'data' => 'Court',
                'style' => 'font-weight: normal; background-color: #f9f9f9; border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                'class' => 'font-sans-2xs',
              ],
              [
                'data' => 'Judge',
                'style' => 'font-weight: normal; background-color: #f9f9f9; border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                'class' => 'font-sans-2xs',
              ],
              [
                'data' => 'Email',
                'style' => 'font-weight: normal; background-color: #f9f9f9; border-width: 0 0 1px 0; border-color: #eee; border-style: solid;',
                'class' => 'font-sans-2xs',
              ],
            ],
            '#rows' => $match_rows,
            '#attributes' => [
              'class' => ['width-full'],
            ],
          ];

          $teacher_data = <<<EOT
<span class="usa-tag">{$visit['county']}</span>
<div class="font-serif-md">{$visit['school_name']}</div>
<div class="margin-top-2 font-serif-md">{$visit['teacher_name']}</div>
<div class="font-mono-2xs">{$visit['email']}</div>
<div class="font-mono-2xs">{$visit['phone']}</div>
<div class="font-mono-2xs">{$visit['grade_level']}</div>
EOT;

          $rows_visit[] = [
            [
              'style' => 'border: 0px solid #ccc;',
            ],
            [
              'data' => [
                '#markup' => $teacher_data,
              ],
              'style' => ['border-width: 0 0 1px 0; border-color: #eee; border-style: solid; padding: 8px;'],
            ],
            [
              'data' => !empty($match_rows) ? render($match_rows_output) : 'No judges available.',
              'style' => ['border-width: 0 0 1px 0; border-color: #ddd; border-style: solid; padding: 8px;'],
            ],
          ];

        }
      }
    }

    $form['table'] = [
      '#type' => 'table',
      '#rows' => $rows_visit,
      '#attributes' => [
        'class' => ['width-full'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
