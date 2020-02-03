<?php

// Edited GitHub Gist by xBeastMode → https://gist.github.com/xBeastMode/89a9d85c21ec5f42f14db49550ea8e5c

namespace Xenophilicy\NaviCompass;

class Query{

    public function __construct($host = '', $port = 19132){
        $this->server = $this->UT3Query($host, $port);
        if ($this->server === null) {
            return false;
        }
        $this->fetchedData =
            [
                'server' => $this->server[1],
                'server_gm' => $this->server[3],
                'server_gn' => $this->server[5],
                'version' => $this->server[7],
                'server_engine' => $this->server[9],
                'plugins' => $this->server[11],
                'server_lobby' => $this->server[13],
                'server_on' => $this->server[15],
                'server_max' => $this->server[17],
                'server_wl' => $this->server[19],
                'server_ip' => $this->server[21],
                'server_port' => $this->server[23],
                'server_online' => implode('<br>', array_slice($this->server, 27))
            ];

        return true;
    }

    public function status(){
        return $this->server === null ? 'offline' : 'online';
    }

    public function getPlayersCount(){
        return $this->fetchedData['server_on'];
    }

    public function getServerMaxPlayers(){
        return $this->fetchedData['server_max'];
    }

    private function UT3Query($host, $port){
        $socket = @fsockopen("udp://" . $host, $port);
        if (!$socket)
            return null;
        $online = @fwrite($socket, "\xFE\xFD\x09\x10\x20\x30\x40\xFF\xFF\xFF\x01");
        if (!$online)
            return null;
        $challenge = @fread($socket, 1400);
        if (!$challenge)
            return null;
        $challenge = substr(preg_replace("/[^0-9-]/si", "", $challenge), 1);
        $query = sprintf("\xFE\xFD\x00\x10\x20\x30\x40%c%c%c%c\xFF\xFF\xFF\x01",
            $challenge >> 24, $challenge >> 16, $challenge >> 8, $challenge >> 0);
        if (!@fwrite($socket, $query))
            return null;
        $response = array();
        $response[] = @fread($socket, 2048);
        $response = implode($response);
        $response = substr($response, 16);
        $response = explode("\0", $response);
        array_pop($response);
        array_pop($response);
        return $response;
    }
}
