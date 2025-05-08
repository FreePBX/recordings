<?php
namespace FreePBX\modules\Recordings\drivers;

class Openai {
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1/';
    private $defaultVoice = 'alloy';
    private $defaultModel = 'tts-1';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public static function getInfo() {
        return array(
            "name" => _("OpenAI")
        );
    }
    
    /**
     * Get available voices list.
     * @return array Voices list
     */
    public function getAvailableVoices() {
        // OpenAI offers 6 predefined voices
        return [
            ['voice_id' => 'alloy', 'name' => 'Alloy'],
            ['voice_id' => 'echo', 'name' => 'Echo'],
            ['voice_id' => 'fable', 'name' => 'Fable'],
            ['voice_id' => 'onyx', 'name' => 'Onyx'],
            ['voice_id' => 'nova', 'name' => 'Nova'],
            ['voice_id' => 'shimmer', 'name' => 'Shimmer']
        ];
    }
    
    /**
     * Convert text to audio with custom options
     * @param string $file_name File name
     * @param string $text Text to convert
     * @param string $voiceId Voice ID (alloy, echo, fable, onyx, nova, shimmer)
     * @return string|bool Path file or false
     */
    public function convertToAudio($file_name, $text, $voiceId = null) {
        global $amp_conf;

        if(empty($text)){
            return ["status" => "false", "message" => _("The text cannot be empty.")];
        }

        try {
            $voice      = $voiceId ?: $this->defaultVoice;
            $postData   = json_encode([
                'model' => $this->defaultModel,
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3' 
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . 'audio/speech');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            
            $audioData = curl_exec($ch);            
            if (curl_errno($ch)) {
                return ["status" => "false", "message" => _("CURL Error: ").curl_error($ch)];
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $_audiodata = json_decode($audioData, true);
                return ["status" => "false", "message" => sprintf( _("Error: %d - %s"), $httpCode, $_audiodata["error"]["code"]) ];
            }
            
            $tmpDir     = $amp_conf["ASTSPOOLDIR"] . "/tmp/";
            $mp3File    = $tmpDir . $file_name . '.mp3';
            $wavFile    = $tmpDir . $file_name . '.wav';
            
            file_put_contents($mp3File, $audioData);
            $command = "ffmpeg -y -i " . escapeshellarg($mp3File) . 
                       " -acodec pcm_s16le -ac 1 -ar 44100 " . 
                       escapeshellarg($wavFile) . " 2>&1";
            exec($command, $output, $returnCode);

            if (file_exists($mp3File)) {
                unlink($mp3File);
            }

            if ($returnCode !== 0 || !file_exists($wavFile)) {
                return ["status" => "false", "message" => _('Error while converting: ') . implode("\n", $output)];
            }
            
            return $file_name . '.wav';
            
        } catch (Exception $e) {
            return ["status" => "false", "message" => _('Error while converting: ') . $e->getMessage()];
        }
    }
}
?>