<?php

namespace App\Services;

class AsteriskService
{
    /**
     * Execute a command on the Asterisk server (locally or via SSH)
     */
    public function execute(string $command): string
    {
        $connection = env('ASTERISK_CONNECTION', 'local');
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($connection === 'ssh' || ($isWindows && env('ASTERISK_SSH_HOST'))) {
            $host = env('ASTERISK_SSH_HOST', '165.227.88.28');
            $port = env('ASTERISK_SSH_PORT', 22);
            $user = env('ASTERISK_SSH_USER', 'root');
            $keyPath = env('ASTERISK_SSH_KEY');
            
            // Build SSH command
            $sshCmd = "ssh -p {$port} -o ConnectTimeout=5 -o StrictHostKeyChecking=no";
            if ($keyPath) {
                $sshCmd .= " -i " . escapeshellarg($keyPath);
            }
            $sshCmd .= " " . escapeshellarg("{$user}@{$host}") . " " . escapeshellarg($command);
            
            return @shell_exec($sshCmd) ?: '';
        }

        if ($isWindows) {
            // Local Windows fallback (mock data)
            return $this->getMockData($command);
        }

        // Direct local execution on Linux
        return @shell_exec($command) ?: '';
    }

    /**
     * Get mock data for local Windows development
     */
    protected function getMockData(string $command): string
    {
        // Return sample channels count for core show channels
        if (strpos($command, 'core show channels') !== false) {
            return <<<EOT
Channel              Context              Extension        Prio State   Application(Data)             Duration

0 active channels

0 active calls
EOT;
        }

        // Return sample PJSIP endpoints matching actual Asterisk output format
        if (strpos($command, 'pjsip show endpoints') !== false) {
            return <<<EOT
 Endpoint:  63311                                                Unavailable   0 of inf
     InAuth:  63311-auth/63311
        Aor:  63311                                              1
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  7788                                                 Unavailable   0 of inf
     InAuth:  7788-auth/7788
        Aor:  7788                                               1
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  VPL-Switch                                           Not in use    0 of inf
        Aor:  VPL-Switch                                         0
      Contact:  VPL-Switch/sip:104.131.49.119:5080         ad01804741 Avail         6.716
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  belloceanic                                          Not in use    0 of inf
        Aor:  belloceanic                                        0
      Contact:  belloceanic/sip:139.59.2.249               fa933914be Avail       204.606
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  ca.didx.net                                          Not in use    0 of inf
        Aor:  ca.didx.net                                        0
      Contact:  ca.didx.net/sip:68.183.206.46              b5bbe6ef5a Avail        16.851
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  eu2.didx.net                                         Not in use    0 of inf
        Aor:  eu2.didx.net                                       0
      Contact:  eu2.didx.net/sip:178.62.98.165             a5d9182d96 Avail        74.083
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  eu3.didx.net                                         Not in use    0 of inf
        Aor:  eu3.didx.net                                       0
      Contact:  eu3.didx.net/sip:46.101.28.27              f46e831faf Avail        69.407
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  from-webRTC-119                                      Unavailable   0 of inf
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  globalbeams-endpoint                                 Not in use    0 of inf
    OutAuth:  globalbeams-auth/4025
        Aor:  globalbeams-aor                                    0
      Contact:  globalbeams-aor/sip:sip.globalbeams.live   ad42de5ffc NonQual         nan
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  sip10.didx.net                                       Not in use    0 of inf
        Aor:  sip10.didx.net                                     0
      Contact:  sip10.didx.net/sip:198.211.99.232          a4a7960d38 Avail         6.819
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

 Endpoint:  us2.didx.net                                         Not in use    0 of inf
        Aor:  us2.didx.net                                       0
      Contact:  us2.didx.net/sip:162.243.253.22            0b9607e3ae Avail         4.415
  Transport:  transport-udp             udp      0      0  0.0.0.0:5060

Objects found: 11
EOT;
        }

        return '';
    }
}
