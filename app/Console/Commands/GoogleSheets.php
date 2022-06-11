<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:sheets';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

    protected $sheets_list = ['https://docs.google.com/spreadsheets/d/1YaFQS-r5DPSZlkI-n2tugBccmkVoE0LWo7qHWDqT50Q/edit#gid=1837096469',
                              'https://docs.google.com/spreadsheets/d/12eDxE-SmtfHDXYGFcOI_z7nXlTx3hSCy3d9VnOctn7I/edit#gid=1837096469'];

    protected $sheets_list_report = [];

    private function send_bot_message($message){
        // Отправляем сообщение в чат группы
        $token = getenv("TOKEN_BOT");
        $chat_id = getenv("CHAT_ID");

        Http::post('https://api.telegram.org/bot'. $token .'/sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
        ]);
    }

    private function get_data($url){
        // Получаем данные из таблицы

        $arr = explode("/", $url);
        $id = $arr[5];
        $gid = explode('=', $arr[6])[1];

        $url = 'https://docs.google.com/spreadsheets/d/' . $id . '/export?format=csv&gid=' . $gid;
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);

        $csv = $response->getBody();
        $csv = explode("\r\n", $csv);
        $array = array_map('str_getcsv', $csv);
        for($i=10; $i<count($array); $i++){
            $per1 = (float) str_replace(",", ".", str_replace("%", "", $array[$i][9]));

            $per2 = (float) str_replace(",", ".", str_replace("%", "", $array[$i][14]));

            if($per1 < 40){
                array_push($this->sheets_list_report, $url);
                break;
            }

            if($per2 < 40){
                array_push($this->sheets_list_report, $url);
                break;
            }

        }



    }

    public function handle()
    {
        // Основной код скрипта

        foreach($this->sheets_list as $url){
            $this -> get_data($url);
        }

        $message = 'Таблицы в которых шанс успеть на неделе-дедлайне меньше 40%: ';

        foreach($this->sheets_list_report as $url){
            $message = $message . $url . "\n\n";
        }

        $this->send_bot_message($message);


        return 0;
    }
}
