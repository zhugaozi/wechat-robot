<?php

namespace Guandaxia\Handlers\Type;

use Carbon\Carbon;
use Guandaxia\Handlers\Service\OfoBike;
use Guandaxia\Handlers\Service\Password;
use Guandaxia\Handlers\Service\Train;
use Hanson\Vbot\Contact\Friends;
use Hanson\Vbot\Contact\Groups;
use Hanson\Vbot\Message\Image;
use Hanson\Vbot\Message\Text;
use Hanson\Vbot\Support\File;
use Illuminate\Support\Collection;

class TextType
{
    private static $ofoLogin = [];
    private static $ofoRepair;

    public static function messageHandler(Collection $message, Friends $friends, Groups $groups)
    {
        $path = realpath(vbot("config")['path'])."/";
        $username = $message['from']['UserName'];
        $isLogin = isset(static::$ofoLogin[$username]);

        if ($message['type'] === 'text') {
             if (strpos($message['content'], '设置天气') === 0) {
                $nickName = $message['from']['NickName'];
//                $uin = $message['from']['Uin'];
                $content = $message['content'];
                $content = explode(" ", $content);
                if (is_array($content) && count($content) > 1) {
                    //设置
                    print_r($content);
                    $city = $content[1];
                    $time = $content[2];

                    $setInfo = [
                        'user_name' => $nickName,
                        'set_time' => $time,
                        'city' => $city,
                        'send_time' => '',
                    ];
                    $timeName = $path . "weather/time.json";
                    if (is_file($timeName)) {
                        $timeArr = file_get_contents($timeName);
                        $timeArr = json_decode($timeArr, true);
                        $find = 0;
                        foreach ($timeArr as $key => $item) {
                            if ($item['user_name'] == $nickName) {
                                $find = 1;
                                $timeArr[$key] = $setInfo;
                            }
                        }
                        if ($find == 0) {
                            $timeArr[] = $setInfo;
                        }
                    } else {
                        $timeArr[] = $setInfo;
                    }
                    file_put_contents($timeName, json_encode($timeArr, JSON_UNESCAPED_UNICODE));

//                $fileName = $path . "weather/". $uin. ".json";
//                file_put_contents($fileName, json_encode($setInfo, JSON_UNESCAPED_UNICODE));

                    $cityName = $path . "weather/city.json";
                    if (is_file($cityName)) {
                        $cityArr = file_get_contents($cityName);
                        $cityArr = json_decode($cityArr, true);
                        $cityArr[] = $city;
                        $cityArr = array_unique($cityArr);
                    } else {
                        $cityArr[] = $city;
                    }
                    file_put_contents($cityName, json_encode($cityArr, JSON_UNESCAPED_UNICODE));

                    Text::send($username, "设置成功");
                } else {
                    //获取设置
                    $timeName = $path . "weather/time.json";
                    if (is_file($timeName)) {
                        $timeArr = file_get_contents($timeName);
                        $timeArr = json_decode($timeArr, true);
                        $info = "";
                        foreach ($timeArr as $item) {
                            if ($item['user_name'] == $nickName) {
                                $info .= "设置时间：" . $item['set_time'] . "\r\n" .
                                    "地点：" . $item['city'];
                                break;
                            }
                        }
                        Text::send($username, $info);
                    }else{
                        $info = "目前还没有设置信息，请按照设置天气+地名+时间的格式设置，如‘设置天气 天津 12:00’";
                        Text::send($username, $info);
                    }
                }
            }
            elseif ($message['content'] === 'time') {
                $datetime = Carbon::parse(vbot('config')->get('server.time'));
                Text::send($message['from']['UserName'], 'Running:' . $datetime->diffForHumans(Carbon::now()));
            }
            elseif ($message['content'] === '图片') {
                $filename = realpath(vbot("config")['path']) . "/ofo/captcha.jpg";
                vbot('console')->log($filename);
                Image::send($username, $filename);
                Text::send($username, '图片发送成功');
            } elseif ($message['content'] === '拉我') {
                $username = $groups->getUsernameByNickname('Vbot 体验群');
                $groups->addMember($username, $message['from']['UserName']);
            } elseif ($message['content'] === '叫我') {
                $username = $friends->getUsernameByNickname('HanSon');
                Text::send($username, '主人');
            } elseif ($message['content'] === '头像') {
                $avatar = $friends->getAvatar($message['from']['UserName']);
                File::saveTo(vbot('config')['user_path'] . 'avatar/' . $message['from']['UserName'] . '.jpg', $avatar);
            } elseif ($message['fromType'] === 'Group' && $message['isAt']) {
               // Text::send($message['from']['UserName'], static::reply($message['pure'], $message['from']['UserName']));
            } elseif ($message['fromType'] === 'Friend') {
                //Text::send($message['from']['UserName'], static::reply($message['content'], $message['from']['UserName']));
            } else {
                //Text::send($message['from']['UserName'], static::reply($message['content'], $message['from']['UserName']));
            }
        }
    }

    private static function reply($content, $id)
    {
        try {
            $result = vbot('http')->post('http://www.tuling123.com/openapi/api', [
                'key' => 'd77bfa2bca5a461ebfa5d9cfec834a28',
                'info' => $content,
                'userid' => $id,
            ], true);

            return isset($result['url']) ? $result['text'] . $result['url'] : $result['text'];
        } catch (\Exception $e) {
            return '图灵API连不上了，再问问试试';
        }
    }
}