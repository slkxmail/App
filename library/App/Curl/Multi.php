<?php

class App_Curl_Multi
{
    protected $_threads = array();

    /**
     * Добавить поток
     *
     * @param string $url
     * @param string $callback
     * @param array  $curlOpts
     */
    public function addTread($url, $callback, $curlOpts = array())
    {
        $thread = array('url' => $url,
            'callback' => $callback,
            'curl_opts' => $curlOpts);

        $this->_threads[] = $thread;
    }

    public function request()
    {
        //create the multiple cURL handle
        $mh = curl_multi_init();

        $running = null;

        # Setup all curl handles
        # Loop through each created curlNode object.
        foreach($this->_threads as $id => $thread){
            $url = $thread['url'];

            $current = new App_Curl();
            $current->setAutodetectEncoding(true);
            $current->setUrl($url);

            # Set defined options, set through curlNode->setOpt();
            if (isset($thread['curl_opts'])){
                foreach($thread['curl_opts'] as $key => $value){
                    $current->setOpt($key, $value);
                }
            }

            curl_multi_add_handle($mh, $current->getHandler());

            $this->_threads[$id]['curl'] = $current;
            $this->_threads[$id]['handler'] = $current->getHandler();
            $this->_threads[$id]['start'] = microtime(1);
        }

        unset($thread);

        # Main loop execution
        do {
            # Exec until there's no more data in this iteration.
            # This function has a bug, it
            while(($execrun = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
            if($execrun != CURLM_OK) break; # This should never happen. Optional line.

            # Get information about the handle that just finished the work.
            while($done = curl_multi_info_read($mh)) {
                # Call the associated listener
                foreach($this->_threads as $id => $thread){
                    # Strict compare handles.
                    if ($thread['handler'] === $done['handle']) {
                        # Get content
                        $curl = $thread['curl'];
                        $curl->parseResult(curl_multi_getcontent($done['handle']));

                        # Call the callback.
                        call_user_func($thread['callback'], $curl, $thread['url']);

                        # Remove unnecesary handle (optional, script works without it).
                        curl_multi_remove_handle($mh, $done['handle']);
                    }
                }

            }
            # Required, or else we would end up with a endless loop.
            # Without it, even when the connections are over, this script keeps running.
            if (!$running) break;

            # I don't know what these lines do, but they are required for the script to work.
            while (($res = curl_multi_select($mh)) === 0);
            if ($res === false) break; # Select error, should never happen.
        } while (true);

        # Finish out our script ;)
        curl_multi_close($mh);
    }
}