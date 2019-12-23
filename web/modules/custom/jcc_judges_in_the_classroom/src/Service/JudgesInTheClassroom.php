<?php

namespace Drupal\jcc_judges_in_the_classroom\Service;

use Drupal\Core\Database\Connection;
use Drupal\webform\Entity\WebformOptions;

/**
 * JudgesInTheClassroom class.
 */
class JudgesInTheClassroom {

  /**
   * Return the type of profile.
   */
  const JUDGE = 'judge';

  /**
   * Return the type of profile.
   */
  const TEACHER = 'teacher';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Counties.
   *
   * @var array
   */
  protected $counties = [];

  /**
   * Days.
   *
   * @var array
   */
  protected $days = [];

  /**
   * Schedule of judges.
   *
   * @var array
   */
  protected $judgeSchedules = [];

  /**
   * Teacher requests.
   *
   * @var array
   */
  protected $teacherRequests = [];

  /**
   * Constructs JudgesInTheClassroom object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    $this->setFilters();
  }

  /**
   * Set parameters for filtering.
   *
   * @param array $options
   *   Options.
   */
  public function setFilters(array $options = []) {

    $this->counties = isset($options['counties']) && !empty($options['counties']) ?
      $options['counties'] :
      array_keys(WebformOptions::load('county')->getOptions());

    $this->days = isset($options['days']) && !empty($options['days']) ?
      $options['days'] :
      array_keys(WebformOptions::load('days')->getOptions());

  }

  /**
   * Get all judges profile and schedule information.
   *
   * @return array
   *   Judges_schedule.
   */
  public function getJudges() {

    if (empty($this->judgeSchedules)) {
      $query = $this->database->query("
        SELECT wsd_day.sid, wsd_day.`value` as day, wsd_county.`value` as county, wsd_preferred_time.`value` as preferred_time, wsd_alternative_time.`value` as alternative_time, wsd_optional_time.`value` as optional_time, wsd_name.`value` as name, wsd_email.`value` as email, wsd_court_name.`value` as court_name
        FROM {webform_submission_data} wsd_day 
          LEFT JOIN {webform_submission_data} wsd_county ON wsd_county.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_preferred_time ON wsd_preferred_time.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_alternative_time ON wsd_alternative_time.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_optional_time ON wsd_optional_time.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_name ON wsd_name.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_email ON wsd_email.`sid` = wsd_day.`sid`
          LEFT JOIN {webform_submission_data} wsd_court_name ON wsd_court_name.`sid` = wsd_day.`sid`
        WHERE wsd_day.`webform_id` = 'judges_in_the_classroom_judges' 
          AND wsd_day.`name` = 'preferred_hours_and_days' 
          AND wsd_day.`property` = 'preferred_day'
          AND wsd_county.`name` = 'county'
          AND wsd_preferred_time.`property` = 'preferred_time'
          AND wsd_day.`delta` = wsd_preferred_time.`delta`
          AND wsd_alternative_time.`property` = 'alternative_time'
          AND wsd_day.`delta` = wsd_alternative_time.`delta`
          AND wsd_optional_time.`property` = 'optional_time'
          AND wsd_day.`delta` = wsd_optional_time.`delta`
          AND wsd_name.`name` = 'name'
          AND wsd_email.`name` = 'email'
          AND wsd_court_name.`name` = 'court_name'
          AND wsd_county.`value` IN (:counties[])
          AND wsd_day.`value` IN (:days[])
      ", [
        ':counties[]' => $this->counties,
        ':days[]' => $this->days,
      ]);
      $judges = $query->fetchAll();

      foreach ($judges as $judge_pref) {

        $hours = [];
        foreach (
          [
            $judge_pref->preferred_time,
            $judge_pref->alternative_time,
            $judge_pref->optional_time,
          ] as $time) {
          if (!empty($time)) {
            $hours[] = substr($time, 0, 2);
          }
        }

        foreach ($hours as $hour) {
          $this->judgeSchedules[$judge_pref->county][$judge_pref->day][$hour][] = [
            'sid' => $judge_pref->sid,
            'name' => $judge_pref->name,
            'email' => $judge_pref->email,
            'court_name' => $judge_pref->court_name,
            'day' => $judge_pref->day,
            'hour' => $hour,
          ];
        }
      }
    }

    return $this->judgeSchedules;
  }

  /**
   * Get all teachers.
   *
   * @return array
   *   Teacher requests.
   */
  public function getTeachers() {

    if (empty($this->teacherRequests)) {
      $query = $this->database->query("
        SELECT wsd_teacher_preferred_time.sid, wsd_teacher_preferred_time.value as preferred_date, DAYNAME(wsd_teacher_preferred_time.value) as preferred_day, LPAD(HOUR(DATE_FORMAT(wsd_teacher_preferred_time.value, '%H:%i:%s')), 2, '0') as preferred_hour, wsd_teacher_county.`value` as county, wsd_teacher_name.`value` as teacher_name, wsd_teacher_email.`value` as email, wsd_teacher_phone.`value` as phone, wsd_teacher_school_name.`value` as school_name, wsd_teacher_grade_level.`value` as grade_level
        FROM {webform_submission_data} as wsd_teacher_preferred_time
          LEFT JOIN {webform_submission_data} as wsd_teacher_county ON wsd_teacher_county.sid = wsd_teacher_preferred_time.sid
          LEFT JOIN {webform_submission_data} as wsd_teacher_name ON wsd_teacher_name.sid = wsd_teacher_preferred_time.sid
          LEFT JOIN {webform_submission_data} as wsd_teacher_email ON wsd_teacher_email.sid = wsd_teacher_preferred_time.sid
          LEFT JOIN {webform_submission_data} as wsd_teacher_phone ON wsd_teacher_phone.sid = wsd_teacher_preferred_time.sid
          LEFT JOIN {webform_submission_data} as wsd_teacher_school_name ON wsd_teacher_school_name.sid = wsd_teacher_preferred_time.sid
          LEFT JOIN {webform_submission_data} as wsd_teacher_grade_level ON wsd_teacher_grade_level.sid = wsd_teacher_preferred_time.sid
        WHERE wsd_teacher_preferred_time.`webform_id` = 'judges_in_the_classroom_teachers'
          AND wsd_teacher_preferred_time.`name` = 'preferred_datetime'
          AND wsd_teacher_county.`name` = 'county'
          AND wsd_teacher_name.`name` = 'teacher_name'
          AND wsd_teacher_email.`name` = 'email'
          AND wsd_teacher_phone.`name` = 'day_phone'
          AND wsd_teacher_school_name.`name` = 'school_name'
          AND wsd_teacher_grade_level.`name` = 'grade_level'
          AND wsd_teacher_preferred_time.value > CURRENT_TIMESTAMP
      ", [
        ':counties[]' => $this->counties,
        ':days[]' => $this->days,
      ]);
      $teachers = $query->fetchAll();

      foreach ($teachers as $t) {
        $this->teacherRequests[$t->county][$t->preferred_day][$t->preferred_hour][] = [
          'sid' => $t->sid,
          'preferred_date' => $t->preferred_date,
          'teacher_name' => $t->teacher_name,
          'email' => $t->email,
          'phone' => $t->phone,
          'school_name' => $t->school_name,
          'grade_level' => $t->grade_level,
        ];
      }
    }

    return $this->teacherRequests;
  }

  /**
   * Get judge matches.
   *
   * @param string $county
   *   County.
   * @param string $day
   *   Day.
   * @param string $hour
   *   Hour.
   * @param string $type
   *   TYpe.
   *
   * @return bool|mixed
   *   Judge matches.
   */
  public function getMatches(string $county, string $day, string $hour = NULL, string $type = self::JUDGE) {

    $profiles = $type == self::JUDGE ? $this->getJudges() : $this->getTeachers();
    $matches = FALSE;

    if ($hour) {
      $matches = isset($profiles[$county][$day][$hour]) ? $profiles[$county][$day][$hour] : FALSE;
    }
    else {
      $matches = isset($profiles[$county][$day]) ? $profiles[$county][$day] : FALSE;
    }

    return $matches;
  }

}
