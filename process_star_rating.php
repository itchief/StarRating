<?php

const
DB_HOST = 'localhost',
DB_NAME = 'mydb',
DB_CHARSET = 'utf8',
DB_USER = 'root',
DB_PASSWORD = '',
MAX_RATING = 5,
IS_CHECK_IP = true;

function log_write($message)
{
    $log = date('d.m.Y H:i:s') . PHP_EOL . $message . PHP_EOL . '-------------------------' . PHP_EOL;
    file_put_contents('error.log', $log, FILE_APPEND);
}

function getIp()
{
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(end(explode(',', $_SERVER[$key])));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return false;
}

$output['result'] = 'error';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    exit(json_encode($output));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit(json_encode($output));
}

$count = 0;
$totalVotes = 1;
if (empty($_POST['id'])) {
    log_write('Не передан id!');
    exit(json_encode($output));
}
$ratingId = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
if (strlen($ratingId) == 0) {
    log_write('Параметр id имеет в качестве значения пустую строку!');
    exit(json_encode($output));
}

try {
    $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    log_write('Подключение не удалось: ' . $e->getMessage());
    exit(json_encode($output));
}

switch ($_POST['action']) {
    case 'get_rating':
        $output['data'] = [
            'rating_avg' => 0,
            'total_votes' => 0
        ];
        try {
            $sql = 'SELECT id, rating_avg, total_votes FROM star_rating WHERE rating_id = :rating_id LIMIT 1';
            $result = $conn->prepare($sql);
            $data = ['rating_id' => $ratingId];
            $result->execute($data);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $output['data'] = [
                    'rating_avg' => $row['rating_avg'],
                    'total_votes' => $row['total_votes']
                ];
                if (IS_CHECK_IP == true) {
                    $sql = 'SELECT count(*) FROM star_rating_ip WHERE rating_id = :rating_id AND rating_ip = :rating_ip';
                    $result = $conn->prepare($sql);
                    $data = ['rating_id' => $row['id'], 'rating_ip' => getIp()];
                    $result->execute($data);
                    $countRows = $result->fetchColumn();
                    if ($countRows == 0) {
                        $output['data']['is_vote'] = false;
                    } else {
                        $output['data']['is_vote'] = true;
                    }
                }
            }
        } catch (PDOException $e) {
            log_write('Ошибка выборки данных: ' . $e->getMessage());
            break;
        }
        $output['result'] = 'success';
        break;

    case 'set_rating':
        if (empty($_POST['rating'])) {
            log_write('Не получено значение рейтинга!');
            break;
        }
        $id = 0;
        $rating = (int)$_POST['rating'];
        if ($rating < 1 || $rating > MAX_RATING) {
            log_write('Полученное значение рейтинга ' . $rating . ' лежит вне допустимого диапазона!');
            break;
        }
        $ratingAvg = $rating;
        try {
            $sql = 'SELECT id, rating_avg, total_votes FROM star_rating WHERE rating_id = :rating_id LIMIT 1';
            $result = $conn->prepare($sql);
            $data = ['rating_id' => $ratingId];
            $result->execute($data);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $count = 1;
                $id = $row['id'];
                $ratingAvg = $row['rating_avg'];
                $totalVotes = $row['total_votes'];
            }
        } catch (PDOException $e) {
            log_write('Ошибка выборки данных: ' . $e->getMessage());
            break;
        }

        if ($count == 0) {
            try {
                $result = $conn->prepare('INSERT INTO star_rating (rating_id, rating_avg, total_votes) VALUES (:rating_id, :rating_avg, :total_votes)');
                $result->execute(['rating_id' => $ratingId, 'rating_avg' => $ratingAvg, 'total_votes' => $totalVotes]);
                if (IS_CHECK_IP == true) {
                    try {
                        $sql = 'SELECT id FROM star_rating WHERE rating_id = :rating_id LIMIT 1';
                        $result = $conn->prepare($sql);
                        $data = ['rating_id' => $ratingId];
                        $result->execute($data);
                        $row = $result->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            try {
                                $result = $conn->prepare('INSERT INTO star_rating_ip (rating_id, rating_value, rating_ip) VALUES (:rating_id, :rating_value, :rating_ip)');
                                $result->execute(['rating_id' => $row['id'], 'rating_value' => $rating, 'rating_ip' => getIp()]);
                            } catch (PDOException $e) {
                                log_write('Ошибка добавления новой записи в таблицу star_rating_ip: ' . $e->getMessage());
                                break;
                            }
                        }
                    } catch (PDOException $e) {
                        log_write('Ошибка выборки данных: ' . $e->getMessage());
                        break;
                    }
                }
            } catch (PDOException $e) {
                log_write('Ошибка добавления новой записи в базу: ' . $e->getMessage());
                break;
            }
        } else {
            $ratingAvg = ($ratingAvg * $totalVotes + $rating) / ($totalVotes + 1);
            $totalVotes = $totalVotes + 1;
            if (IS_CHECK_IP == true) {
                $ip = getIp();
                $sql = 'SELECT count(*) FROM star_rating_ip WHERE rating_id = :rating_id AND rating_ip = :rating_ip';
                $result = $conn->prepare($sql);
                $data = ['rating_id' => $id, 'rating_ip' => $ip];
                $result->execute($data);
                $countRows = $result->fetchColumn();
                if ($countRows > 0) {
                    break;
                }
                try {
                    $result = $conn->prepare('INSERT INTO star_rating_ip (rating_id, rating_value, rating_ip) VALUES (:rating_id, :rating_value, :rating_ip)');
                    $result->execute(['rating_id' => $id, 'rating_value' => $rating, 'rating_ip' => $ip]);
                } catch (PDOException $e) {
                    log_write('Ошибка добавления новой записи в таблицу star_rating_ip: ' . $e->getMessage());
                    break;
                }
            }
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