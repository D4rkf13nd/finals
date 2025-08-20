<?php

define('BACKUP_DIR', __DIR__ . '/backups');

// Create backup directory if it doesn't exist
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Backup database
$backup_file = BACKUP_DIR . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
$command = sprintf(
    'mysqldump --user=%s --password=%s %s > %s',
    DB_USER,
    DB_PASS,
    DB_NAME,
    $backup_file
);

system($command);

// Compress backup
$zip = new ZipArchive();
$zip_file = $backup_file . '.zip';
$zip->open($zip_file, ZipArchive::CREATE);
$zip->addFile($backup_file, basename($backup_file));
$zip->close();

// Remove uncompressed SQL file
unlink($backup_file);

echo "Backup completed: " . basename($zip_file);