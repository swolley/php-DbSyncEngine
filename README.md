# php-DbSyncEngine
###php process to sync db tables

<p>
  DESCRIPTION<br/>
  I needed to sync remote db's instances.<br/>
  Can be cronned to system scheduler and actually outputs to stdout if any insert or update has been executed or if any error occured.<br/>
  It can be interesting to handle log files or to send mails on errors.
</p>
<p>
  SETTINGS<br/>
  set "config.php" for db connections<br/>
  set "maps.php" for entities mapping rules<br/>
  create "connector.log" and "lastsync" files with read/write permissions<br/>
</p>
<p>
  CHRONTAB<br/>
  */15 * * * * /usr/bin/php /path/to/index.php >> /path/to/connector.log
</p>
