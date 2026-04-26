<?php
chdir(dirname(__DIR__));

include __DIR__ . '/../includes/data.php';

$latestRecord = !empty($wait_history_records) ? $wait_history_records[count($wait_history_records) - 1] : null;
$status = [
    'ran_at' => date('c'),
    'live_available' => $live_is_available,
    'sync_label' => $sync_short_label,
    'open_count' => $open_count,
    'closed_count' => $closed_count,
    'untracked_count' => $untracked_count,
    'avg_wait' => $avg_wait,
    'history_records' => count($wait_history_records),
    'latest_history_timestamp' => $latestRecord['timestamp'] ?? null,
];

writeJsonStore('collector-status.json', $status);

if ($live_is_available) {
    echo '[collector] OK ' . $sync_short_label . ' - ' . $open_count . ' waits collectes' . PHP_EOL;
    exit(0);
}

fwrite(STDERR, '[collector] API indisponible, aucune nouvelle releve valide enregistree' . PHP_EOL);
exit(1);
