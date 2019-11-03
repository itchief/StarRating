<?php

const
  DSN = 'mysql:host=localhost;dbname=mydb',
  DB_USER = 'root',
  DB_PASSWORD = '';

function log_write($message)
{
    $log = date('d.m.Y H:i:s') . PHP_EOL . $message . PHP_EOL . '-------------------------' . PHP_EOL;
    file_put_contents('error.log', $log, FILE_APPEND);
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    exit(json_encode($output));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit(json_encode($output));
}

$output['result'] = 'error';

$count = 0;
$totalVotes = 1;
$ratingId = filter_var($_POST['id'], FILTER_SANITIZE_STRING);

try {
    $conn = new PDO(DSN, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    log_write('Подключение не удалось: ' . $e->getMessage());
    exit(json_encode($output));
}

switch ($_POST['action']) {
    case 'get_rating':
        if (empty($_REQUEST['id'])) {
            break;
        }
        $output['data'] = [
            'rating_avg' => 0,
            'total_votes' => 0
        ];
        try {
            $sql = 'SELECT rating_avg, total_votes FROM star_rating WHERE rating_id = :rating_id LIMIT 1';
            $result = $conn->prepare($sql);
            $data = ['rating_id' => $ratingId];
            $result->execute($data);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $output['data'] = [
                    'rating_avg' => $row['rating_avg'],
                    'total_votes' => $row['total_votes']
                ];
            }
        } catch (PDOException $e) {
            log_write('Ошибка выборки данных: ' . $e->getMessage());
            break;
        }
        $output['result'] = 'success';      
        break;

    case 'set_rating':
        if (empty($_POST['id']) || empty($_POST['rating'])) {
            break;
        }
        $rating = (int)$_POST['rating'];
        $ratingAvg = $rating;
        try {
            $sql = 'SELECT id, rating_avg, total_votes FROM star_rating WHERE rating_id = :rating_id LIMIT 1';
            $result = $conn->prepare($sql);
            $data = ['rating_id' => $ratingId];
            $result->execute($data);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $count = 1;
                $ratingAvg = $row['rating_avg'];
                $totalVotes = $row['total_votes'];
            }
        } catch (PDOException $e) {
            log_write('Ошибка выборки данных: ' . $e->getMessage());
            break;
        }
        
        if ($count === 0) {
            try {
                $result = $conn->prepare('INSERT INTO star_rating (rating_id, rating_avg, total_votes) VALUES (:rating_id, :rating_avg, :total_votes)');
                $result->execute(['rating_id' => $ratingId, 'rating_avg' => $ratingAvg, 'total_votes' => $totalVotes]);
            } catch (PDOException $e) {
                log_write('Ошибка добавления новой записи в базу: ' . $e->getMessage());
                break;
            }
        } else {
            $ratingAvg = ($ratingAvg * $totalVotes + $rating) / ($totalVotes + 1);
            $totalVotes = $totalVotes + 1;
            $sql = 'UPDATE star_rating SET rating_avg=:rating_avg, total_votes=:total_votes WHERE rating_id=:rating_id';
            $data = [
              'rating_id' => $ratingId,
              'rating_avg' => $ratingAvg,
              'total_votes' => $totalVotes
            ];
            try {
                $conn->prepare($sql)->execute($data);
            } catch (PDOException $e) {
                log_write('Ошибка добавления записи с rating_id = ' . $ratingId . ': ' . $e->getMessage());
                break;
            }
        }
        
        $output['result'] = 'success';
        
        $output['data'] = [
          'rating_avg' => $ratingAvg,
          'total_votes' => $totalVotes
        ];
        break;
}

header('Content-Type: application/json');
exit(json_encode($output));