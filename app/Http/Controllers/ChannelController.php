<?php

namespace App\Http\Controllers;
use Google_Client;
use Illuminate\Support\Facades\Storage;

class ChannelController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function fetch()
    {
        return $this->youtube_search(50);

     }

     public function getData($data) {
         $headers = ['title', 'description', 'country', 'subscriberCount'];
         $csv_filename = 'youtube'."_".date("Y-m-d_H-i",time()).".csv";
         $fh = fopen($csv_filename, "w");
         fputcsv($fh, $headers);

         foreach($data as $field) {
            fputcsv($fh, $field);
         }
         fclose($fh);
    }

    public function youtube_search($max_results, $next_page_token=''){
        $client = new Google_Client(); //GuzzleHttp\Client
        $client->setDeveloperKey("AIzaSyAiQ9hHkdopsuFWKiVVAGJFRLhQPWlnLHk");
        $service = new \Google_Service_YouTube($client);
        $params = [
            "maxResults" => $max_results,
            "forUsername"    => "LeegendaryaFilms"
            //"id" => "UCkm2sCI24fqfklJj8AsBQZg"
            ];

            // if next_page_token exist add 'pageToken' to $params
        if(!empty($next_page_token)){
            $params['pageToken'] = $next_page_token;
        }

              // than first loop
        $searchResponse = $service->channels->listChannels('brandingSettings', $params);
        //dd($searchResponse);
       $ids = $searchResponse->items[0]->brandingSettings->channel->featuredChannelsUrls;
        $contents = [];
        $complete = [];
        foreach($ids as $t) {
            //dd($t);
                $channel = $t;
                 $items = $service->channels;
                    $param = [
                        "id"    => $channel,
                        "maxResults" => 50
                    ];

                    $descriptions = $items->listChannels('snippet', $param);

                    $country = isset($descriptions->items[0]->snippet->country) ? $descriptions->items[0]->snippet->country : null;

                    $channel_id = $channel;
                    $api_key = "AIzaSyC2wZfl2PU8OeewhcD2P4Ey7lPMbo96Gno";
                    $api_response = file_get_contents('https://www.googleapis.com/youtube/v3/channels?part=statistics&id='.$channel_id.'&fields=items/statistics/subscriberCount&key='.$api_key);
                    $api_response_decoded = json_decode($api_response, true);
                    $subscriberCount = isset($api_response_decoded['items'][0]['statistics']['subscriberCount']) ? $api_response_decoded['items'][0]['statistics']['subscriberCount'] : null;

                    if($country == 'DE' && $subscriberCount >= 5000) {
                        $content[] = [
                            "title" => $descriptions->items[0]->snippet->title,
                            "description" => $descriptions->items[0]->snippet->description,
                            "country"   => $descriptions->items[0]->snippet->country,
                            "subscribers" => $subscriberCount
                        ];
                        $contents = $content;
                    }

                }
                return $this->getData($contents);
          // checking if nextPageToken exist than return our function and
          // insert $next_page_token with value inside nextPageToken
        if(isset($searchResponse['nextPageToken'])){
              // return to our function and loop again
              $this->getData($contents);
            return $this->youtube_search($max_results, $searchResponse['nextPageToken']);

        }

    }

}