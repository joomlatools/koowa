<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2007 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa for the canonical source repository
 */

/**
 * Http Dispatcher Response Transport
 *
 * Pass all 'html' GET requests rendered outside of 'koowa' context on to Joomla.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher
 */
class ComKoowaDispatcherResponseTransportHttp extends KDispatcherResponseTransportHttp
{
    /**
     * Send HTTP response
     *
     * @param KDispatcherResponseInterface $response
     * @return boolean
     */
    public function send(KDispatcherResponseInterface $response)
    {
        $request = $response->getRequest();

        if($request->getFormat() == 'html')
        {
            //Render the page
            $this->getObject('com:koowa.controller.page',  array('response' => $response))
                ->layout($request->query->get('tmpl', 'cmd') == 'koowa' ? 'koowa' : 'joomla')
                ->render();


            //Pass back to Joomla
            if ($request->isGet() && $request->query->get('tmpl', 'cmd') != 'koowa')
            {
                //Mimetype
                JFactory::getDocument()->setMimeEncoding($response->getContentType());

                //Headers
                $headers = explode("\r\n", trim((string) $response->headers));
                foreach ($headers as $header)
                {
                    $parts = explode(':', $header, 2);

                    if (count($parts) !== 2) { // Empty values are not allowed per RFC2616 Sec 4.2
                        continue;
                    }

                    JResponse::setHeader($parts[0], $parts[1]);
                }

                //Cookies
                foreach ($response->headers->getCookies() as $cookie)
                {
                    setcookie(
                        $cookie->name,
                        $cookie->value,
                        $cookie->expire,
                        $cookie->path,
                        $cookie->domain,
                        $cookie->isSecure(),
                        $cookie->isHttpOnly()
                    );
                }

                //Messages
                $messages = $response->getMessages(false);
                foreach($messages as $type => $group)
                {
                    if ($type === 'success') {
                        $type = 'message';
                    }

                    foreach($group as $message) {
                        JFactory::getApplication()->enqueueMessage($message, $type);
                    }
                }

                //Content
                echo $response->getContent();

                return true;
            }
        }

        return parent::send($response);
    }
}