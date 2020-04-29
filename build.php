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
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf8">
    <title>proxima</title>
<style>
/* reset global styling */

* {
  margin: 0;
  box-sizing: border-box;
  cursor: default;
}

html, body, #container {
  width: 100%;
  height: 100%;
  font-size: 10pt;
  font-family: -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial, sans-serif;
}

textarea {
  font: inherit;
}

.pre {
  white-space: pre;
  font-family: monospace;
  font-size: 12px;
}

/* color variable declarations */

:root {
  --main-light: #abe;
  --main: #38f;
  --light: #bbb;
  --very-light: #eee;
}

/* app */

a {
  color: var(--main);
  cursor: pointer;
}

a:visited {
  color: var(--main-light);
}

.app {
  display: flex;
  width: 100%;
  height: 100%;
  min-height: 100%;
  max-height: 100%;
}

.app h1 a {
  color: var(--main);
}

.main {
  height: 100%;
  /*max-height: 100%;*/
  width: 80%;
  overflow-wrap: break-word;
  overflow-y: scroll;
}

.side {
  padding: 10px;
  background: var(--very-light);
  width: 20%;
  overflow-wrap: break-word;
  overflow-y: scroll;
}

.side .peer.in-network {
  color: var(--light);
}

/* chatbar */

.chatbar {
  padding: 5px;
  border-top: 1px solid var(--light);
  display: flex;
  align-items: center;
}

.chatbar .nick {
  margin-right: 5px;
}

.chatbar textarea {
  flex: 1;
  margin-right: 5px;
}

/* wall */

.wall {
  padding: 10px;
  overflow-wrap: break-word;
}

.user {
  display: inline-block;
  margin-top: 15px;
  margin-right: 5px;
  text-decoration: none;
}

.post {
  display: inline-block;
}

.post info {
  opacity: 0.4;
  font-size: 9px;
  vertical-align: middle;
}

.post info * {
  color: var(--light);
}

.post:hover info {
  opacity: 1;
}

.post {
  /*margin: 10px 15px 0 0;*/
}

.post .post {
  /*margin: 10px 15px;*/
}
</style>
  </head>
  <body>
    <div id="container"></div>
    <script>localStorage.token = window.token = localStorage.token || '<?php echo bin2hex(random_bytes(16)) ?>'</script>
<script>
(function () {
  'use strict';

  function forEvent (emitter, name) {
    return new Promise(resolve => {
      emitter.addEventListener(name, function listener (event) {
        emitter.removeEventListener(name, listener);
        resolve(event);
      });
    })
  }

  const OPTIONS = {
    iceServers: []
  };

  class Peer {
    constructor (opts = OPTIONS) {
      this.connection = new RTCPeerConnection(opts);
    }

    open (offer) {
      if (offer) {
        return this.createAnswer(offer)
      } else {
        return this.createOffer()
      }
    }

    close () {
      return this.connection.close()
    }

    async createOffer () {
      this.channel = this.connection.createDataChannel('data');
      await forEvent(this.connection, 'negotiationneeded');
      const offer = await this.connection.createOffer();
      return this.createSignal(offer)
    }

    async createAnswer (offer) {
      await this.connection.setRemoteDescription(offer);
      const answer = await this.connection.createAnswer();
      return this.createSignal(answer)
    }

    async createSignal (signal) {
      signal.sdp = signal.sdp.replace(/a=ice-options:trickle\s\n/g, '');
      await this.connection.setLocalDescription(signal);
      await Promise.race([
        forEvent(this.connection, 'icecandidate'),
        secs(30)
      ]);
      return this.connection.localDescription
    }

    async connect (answer) {
      if (answer) {
        await this.connection.setRemoteDescription(answer);
      } else {
        this.channel = (await forEvent(this.connection, 'datachannel')).channel;
      }
      return forEvent(this.channel, 'open')
    }
  }

  let base = 'http://localhost'; //document.location.origin

  const json = res => res.json();
  const headers = {
    Accept: 'application/json',
    Authorization: `Bearer ${window.token}`
  };
  const get = (url, opts) => fetch(url, Object.assign(opts, headers)).then(json);
  const post = (url, data) => {
    const body = new FormData();
    Object.keys(data).forEach(key => body.append(key, data[key]));
    return get(url, { method: 'POST', body })
  };

  const getNextOffer = () => get(base);

  // server php encrypts id and we send it back on every request on header

  const sendOffer = async sdp => await post(base, { sdp });
  const sendAnswer = async (id, sdp) => await post(base, { id, sdp });
    // if (!notKnownPeer(id)) throw new Error('Answer aborted, known peer')
  // }
  const getAnswer = async offer => await get(`${base}/?id=answers/${offer.id}`);

  const waitForAnswer = async (offer, retries = 10) => {
    for (let i = 0, answer; i < retries; i++) {
      try {
        // console.log('waiting for answer:', offer.id)
        answer = await getAnswer(offer.id);
        answer.sdp = JSON.parse(answer.sdp);
        return answer
      } catch (error) {
        await secs();
      }
    }
    throw new Error('Waiting for answer failed: Max retries reached')
  };

  function connect () {
    makeOffers();
    makeAnswers();
  }

  async function makeOffers () {
    do {
      try {
        net.addPeer(await createPeer('offer', serverHandshake));
      } catch (error) {
        console.error(error);
      }
    } while (await lessThanMaxPeers() && await secs(3 + peers.length ** 5))
  }

  async function makeAnswers () {
    do {
      try {
        net.addPeer(await createPeer('answer', serverHandshake));
      } catch (error) {
        console.error(error);
      }
    } while (await lessThanMaxPeers() && await secs(3 + peers.length ** 5))
  }

  async function createPeer (type, handshake) {
    const peer = new Peer();
    try {
      switch (type) {
        case 'offer':
          // public/private key generated on init
          // peer broadcasts public key
          // other peers use public key to encrypt a message(offer) to be whispered
          // only peer with private key can decrypt the message
          // channel.offer should contain an id known only to this peer
          // so that we can use it to delete the offer on completion/failure
          await peer.connect(await handshake(await peer.open()));
          break

        case 'answer':
          await handshake(await peer.open(await handshake()));
          await peer.connect();
          break
      }
      return peer
    } catch (error) {
      peer.close();
      throw error
    }
  }

  async function serverHandshake (signal) {
    if (signal) {
      switch (signal.type) {
        case 'offer':
          const offer = await sendOffer(signal);
          const answer = await waitForAnswer(offer);
          return answer

        case 'answer':
          await sendAnswer(signal);
          break
      }
    } else {
      const offer = await getNextOffer();
      return offer
    }
  }


  // loop: for await (const event of channel.event()) {
  //   switch (event.type) {
  //     case 'open':
  //       //
  //       break loop
  //     case 'close':
  //       //
  //       break loop
  //     case 'message':
  //       //
  //       break
  //   }
  // }

  connect();

}());
</script>
  </body>
</html><?php
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
          $offers = array_slice(scandir($datadir . 'offers'), 2);
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
      if (!$token) exit(1);

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