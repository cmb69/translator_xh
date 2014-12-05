<?php

class CSRFAttackTest extends PHPUnit_Framework_TestCase
{
    protected $url;

    protected $curlHandle;

    protected $cookieFile;

    /**
     * Log in to back-end and store cookies in a temp file.
     */
    public function setUp()
    {
        $this->url = 'http://localhost' . getenv('CMSIMPLEDIR');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'CC');

        $this->curlHandle = curl_init($this->url . '?&login=true&keycut=test');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($this->curlHandle);
        curl_close($this->curlHandle);
    }

    protected function setCurlOptions($fields)
    {
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile
        );
        curl_setopt_array($this->curlHandle, $options);
    }

    public function dataForAttack()
    {
        return array(
            array( // generate ZIP
                array(
                    'translator_modules[]' => 'CORE',
                    'translator_filename' => 'foo'
                ),
                'translator&admin=plugin_main&action=zip&translator_lang=de'
            ),
            array( // save editor
                array(),
                'translator&admin=plugin_main&action=save&translator_from=en&translator_to=de&translator_module=CORE'
            )
        );
    }

    /**
     * @dataProvider dataForAttack
     */
    public function testAttack($fields, $queryString = null)
    {
        $url = $this->url . (isset($queryString) ? '?' . $queryString : '');
        $this->curlHandle = curl_init($url);
        $this->setCurlOptions($fields);
        curl_exec($this->curlHandle);
        $actual = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        curl_close($this->curlHandle);
        $this->assertEquals(403, $actual);
    }

}

?>
