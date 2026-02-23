<?php
namespace FreePBX\modules\Recordings\drivers;

class Elevenlabs {
    private $apiKey;
    private $apiUrl = 'https://api.elevenlabs.io/v1/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public static function getInfo() {
		return array(
			"name" => _("Elevenlabs")
		);
	}
    
    /**
     * Get available voices list.
     * @return array Voices list
     */
    public function getAvailableVoices() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . 'voices');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'xi-api-key: ' . $this->apiKey
            ]);
            
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception('Erreur cURL: ' . curl_error($ch));
            }
            
            curl_close($ch);
            $data = json_decode($response, true);
            return $data['voices'] ?? [];
            
        } catch (Exception $e) {
            dbug( _('Error retrieving voices ') . $e->getMessage() );
            return [];
        }
    }
    
    /**
     * Convert text to audio with custom options
     * @param string $file_name Tile name
     * @param string $text Texte to convert
     * @param string $voiceId Voice ID
     * @param string $langCode language code
     * @param float $stability Stability (0-1) - influences speed
     * @param float $similarity Similarity (0-1) - influences tone
     * @return string|bool Path file or false
     */
    public function convertToAudio($file_name, $text, $voiceId, $langCode = 'fr', $stability = 0.5, $similarity = 0.5) {
        global $amp_conf;
        
        try {
            // Validate filename against a strict allowlist (alphanumeric, underscore, dash, dot only)
            if (!preg_match('/^[a-zA-Z0-9._-]+$/', $file_name)) {
                throw new \Exception('Invalid filename: only alphanumeric characters, dots, underscores and dashes are allowed');
            }

            $postData = json_encode([
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => $stability,  
                    'similarity_boost' => $similarity 
                ]
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . 'text-to-speech/' . $voiceId);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: audio/mpeg',
                'Content-Type: application/json',
                'xi-api-key: ' . $this->apiKey
            ]);
            
            $audioData = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('Erreur cURL: ' . curl_error($ch));
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception('Erreur API: Code HTTP ' . $httpCode);
            }
            
            // Sanitize filename to prevent command injection
            $file = basename($file_name);
            $tmpDir = $amp_conf["ASTSPOOLDIR"] . "/tmp/";
            $mp3File = $tmpDir . $file . '.MP3';
            $wavFile = $tmpDir . $file . '.wav';
            
            file_put_contents($mp3File, $audioData);
            if(file_exists($wavFile)){
                unlink($wavFile);
            }

            // Use escapeshellarg() to prevent command injection
            $command = "ffmpeg -y -i " . escapeshellarg($mp3File) . 
                       " -acodec pcm_s16le -ac 1 -ar 44100 " . 
                       escapeshellarg($wavFile) . " 2>&1";
            exec($command, $output, $returnCode);

             
            if(file_exists($mp3File)){
                unlink($mp3File);
            }
            
            return $file . '.wav';
            
        } catch (Exception $e) {
            dbug( _('Error while converting: ') . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            dbug( _('Error while converting: ') . $e->getMessage());
            return false;
        }

    }
}

?>
