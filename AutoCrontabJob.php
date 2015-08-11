<?php

/** AutoCrontabJob and related classes */

namespace Battis;

/**
 * An object-oriented approach to scheduling regular cron jobs automatically
 *
 * @author Seth Battis <seth@battis.net>
 **/
abstract class AutoCrontabJob {
	
	/** @var \TiBeN\CrontabManager\CrontabRepository	Crontab management */
	private $cron = null;
	/**
	 * Construct a new CanvasDataCollector
	 *
	 * @param string $identifier Hopefully unique!
	 * @param string $script Path to data collection script (probably `__FILE__`)
	 * @param string|TiBeN\CrontabManager\CrontabJob Cron schedule or a complete TiBeN\CrontabManager\CrontabJob
	 *
	 * @throws CanvasDataCollector_Exception CONSTRUCTOR_ERROR If parameters are not validated
	 **/
	public function __construct($identifier, $script, $schedule) {
		
		/* Make sure the scheduled script file exists... */
		if (file_exists($script)) {
			
			/* try to make the identifier truly unique to this instance */
			$_identifier  = $identifier . '.' . md5($identifier . $schema . $log . __FILE__ . __CLASS__);
			
			/* ensure that we're working with a valid Cron job */
			$newJob = null;
			if (is_string($schedule)) {
				$newJob = TiBeN\CrontabManager\CrontabJob::createFromCrontabLine($schedule . " php $script");
			} elseif ($schedule instanceof TiBeN\CrontabManager\CrontabJob) {
				$newJob = $schedule;
			} else {
				throw new CanvasDataCollector_Exception(
					'Expected a string or TiBeN\CrontabManager\CrontabJob, received ' . print_r($schedule, true),
					CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
				);
			}
			$newJob->comments = implode(' ', array($newJob->comments, "[Created by CanvasDataCollector (Job ID $_identifier) " . date('Y-m-d h:ia')  . ']'));

			
			/* update cron if this job already exists */
			$this->cron = new TiBeN\CrontabManager\CrontabRepository(new TiBeN\CrontabManager\CrontabAdapter());
			if (!empty($results = $this->cron->findJobByRegex("/$_identifier/"))) {
				$job = $results[0];
				$job->minutes = $newJob->minutes;
				$job->hours = $newJob->hours;
				$job->dayOfMonth = $newJob->dayOfMonth;
				$job->months = $newJob->months;
				$job->dayOfWeek = $newJob->dayOfWeek;
			
			/* ... or add this as a new job if it doesn't exist */
			} else {
				$this->cron->addJob($newJob);
			}
			
			/* set up logging */
			if (is_string($log)) {
				$this->log = Log::singleton('file', $log);
				if (!($this->log instanceof Log)) {
					throw new CanvasDataCollector_Exception(
						"Invalid log file location '$log'",
						CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
					);
				}
			}
			
			/* update cron to enable scheduled jobs */
			$this->cron->persist();
			
		} else {
			throw new CanvasDataCollector(
				"PHP script '$script' does not exist",
				CanvasDataCollector_Exception::CONSTRUCTOR_ERROR
			);
		}
	}
	
	/**
	 * Scheduled job
	 *
	 * Override this method to do something scheduled by Cron.
	 *
	 * @return void
	 **/
	public abstract function scheduledJob();
}

/**
 * All exceptions thrown by AutoCrontabJob
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class AutoCrontabJob_Exception extends \Exception {

	/** Error constructing CanvasDataCollector */
	const CONSTRUCTOR_ERROR = 1;
	
	/** Error making a MySQL query */
	const MYSQL_ERROR = 2;
}
	
?>