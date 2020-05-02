<?php

  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: *');
  header('Access-Control-Allow-Headers: *');

  $datadir = getcwd() . '/signals/';
  if (!is_dir($datadir)) {
    mkdir($datadir . 'offers', 0777, true);
    mkdir($datadir . 'answers', 0777, true);
  }

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $accept = explode(',', $_SERVER['HTTP_ACCEPT'])[0];
      if ($accept === 'text/html' || $accept === '*/*') {
        // TODO: generate asymetric encryption tokens based on email + pass + secret?
        include 'main.html';
      } else if ($accept === 'application/json') {
        header('Content-Type: application/json');
        $token = explode(' ', apache_request_headers()['Authorization'])[1];
        if (!$token) {
          http_response_code(401); // Unauthorized
          exit(1);
        }

        if (isset($_GET['id'])) {
          $id = $_GET['id'];
          $filename = $datadir . 'answers/' . $id;
          $handle = fopen($filename, 'r+');
          flock($handle, LOCK_EX);
          $size = filesize($filename);
          if (!$handle || !$size) {
            http_response_code(404);
            exit(1);
          }
        } else {
          $cid = substr($token, -5);
          $not = explode(',', $_GET['not']);
          array_push($not, $cid);
          $offers = array_reverse(array_slice(scandir($datadir . 'offers'), 2));
          $offers = array_filter($offers, function ($v) {
            global $not;
            $offer_cid = array_pop(explode('.', $v));
            return !in_array($offer_cid, $not);
          });
          if (!$offers) {
            http_response_code(404);
            exit(1);
          }
          foreach ($offers as $id) {
            $filename = $datadir . 'offers/' . $id;
            $handle = fopen($filename, 'r+');
            if (!$handle) continue;
            if (!flock($handle, LOCK_EX)) continue;
            $size = filesize($filename);
            if (!$size) continue;
            break;
          }
          if (!$handle) {
            http_response_code(404);
            exit(1);
          }
        }

        $contents = fread($handle, $size);
        rewind($handle);
        ftruncate($handle, 0);
        flock($handle, LOCK_UN);
        unlink($filename);
        echo $contents;
      } else {
        http_response_code(415); // Unsupported media type
      }
    break;

    case 'POST':
      header('Content-Type: application/json');
      $token = explode(' ', apache_request_headers()['Authorization'])[1];
      if (!$token) {
        http_response_code(401); // Unauthorized
        exit(1);
      }

      $cid = substr($token, -5);
      $signal = $_POST;
      if (isset($_POST['id'])) {
        $prefix = 'answers/';
        $id = $_POST['id'];
        $signal['cid'] = $cid;
      } else {
        $prefix = 'offers/';
        $id = microtime(true) . '.' . $cid;
        $signal['id'] = bin2hex(random_bytes(16));
        $signal['cid'] = $cid;
      }
      $json = json_encode($signal);
      $filename = $datadir . $prefix . $id;
      file_put_contents($filename, $json);
      echo $json;
    break;

    case 'OPTIONS':
      break;

    default:
      http_response_code(405); // Method not allowed
      exit(1);
  }
?>