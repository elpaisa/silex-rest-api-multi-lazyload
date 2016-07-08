<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Language controller, helps get all language related stuff, it uses Yandex service for phrase translation
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Language;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Yandex\Translate\Translator;
use Yandex\Translate\Exception;

class LanguageController extends \App\Controller
{

    /**
     * Array of endpoints to expose in the api
     *
     * @return array
     */
    public function endpoints()
    {
        $endpoint = $this->getControllerName();

        return array(
            'get'    => array(
                $endpoint              => $endpoint . ".controller:getAll",
                $endpoint . "/phrases" => $endpoint . ".controller:phrases"
            ),
            'post'   => array(
                $endpoint => $endpoint . ".controller:save",
                $endpoint."/getPhrases" => $endpoint . ".controller:getPhrases"
            ),
            'put'    => array(
                $endpoint . "/{id}" => $endpoint . ".controller:update"
            ),
            'delete' => array(
                $endpoint . "/{id}" => $endpoint . ".controller:delete"
            )
        );

    }

    /**
     * Gets a list of phrases for the user language
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPhrases(Request $request)
    {
        
        return new JsonResponse($this->getPhrasesByList($this->getDataFromRequest($request)));
    }
    
    /**
     * @param array $varNames
     * @return array
     */
    public function getPhrasesByList($varNames)
    {
        if (!is_array($varNames) || count($varNames) === 0) {
            throw new InvalidParameterException($this->lang('invalid_language_received'));
        }
    
        $phrases = array();
        foreach ($varNames as $varName) {
            $phrases[$varName] = $this->findPhrase($varName);
        }
        
        return $phrases;
    }

    /**
     * Get languages
     */
    public function phrases()
    {
        $language = $this->service->getAll();
        $phrases  = array();

        foreach ($language as $phrase) {
            $phrases[$phrase['var_name']] = $phrase['value'];
        }

        return new JsonResponse($phrases);
    }
    
    /**
     * @param string $varName
     * @return string
     */
    public function findPhrase($varName)
    {
        $lang   = isset($this->api['user.lang']) ? $this->api['user.lang'] : $this->api['global.config']['lang'];
        $phrase = $this->service->findByVarName($varName, $lang);
        
        if (!$phrase || count($phrase) === 0) {
            $phrase = trim($varName);
            $words  = explode("_", $phrase);
            $phrase = ucfirst(implode(" ", $words));
            $phrase = $this->yandex($varName, $phrase, $lang);

        } else {
            $phrase = $phrase['value'];
        }

        return $phrase;
    }

    /**
     * Gets the translation from Yandex free API and saves it locally.
     * Someday may go away as free, :(
     *
     * @param string $varName
     * @param string $string
     * @param string $lang
     * @return string
     */
    public function yandex($varName, $string, $lang)
    {

        try {
            $translator = new Translator($this->api['global.config']['yandex_api_token']);
            $tr         = $translator->translate($string, "en-$lang");
            $result     = $tr->getResult();

            if (isset($result[0])) {
                $string = $result[0];
                $this->service->addPhrase($varName, $string, $lang);
            }

            return $string;

        } catch (Exception $e) {
            return $string;
        }


    }

}
