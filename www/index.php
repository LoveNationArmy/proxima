<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: *');
  header('Access-Control-Allow-Headers: *'); 

  switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      header('Content-Type: application/json');
      if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $filename = getcwd() . '/data/' . $id;
        echo file_get_contents($filename);
      } else {
        echo json_encode(array_slice(array_slice(scandir(getcwd() . '/data/offers'), 2), -6));
      }
    break;

    case 'POST':
      header('Content-Type: application/json');
      if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $prefix = 'answers';
      } else {
        $prefix = 'offers';
        $salt = bin2hex(random_bytes(5));
        $hash = hash_hmac('ripemd160', $_POST['d'], $salt);
        $id = time() . '.' . $hash . '.' . $_POST['cid'];
      }
      $item = (object) ['id' => $id, 'cid' => $_POST['cid'], 'd' => $_POST['d']];
      $json = json_encode($item);
      $filename = getcwd() . '/data/' . $prefix . '/' . $id;
      file_put_contents($filename, $json);
      echo $json;
    break;

    case 'DELETE':
      parse_str($_SERVER['QUERY_STRING']);
      unlink(getcwd() . '/data/offers/' . $id);
      unlink(getcwd() . '/data/answers/' . $id);
      echo 'OK';
    break;

    default:
  }
?>