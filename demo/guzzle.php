<?php
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class guzzle
{
	// 并发POST
	public function poolPost($dataArr,$cut=20,$concurrency=50,$circle=1,$useProxy = false,$config = [],$ismin = true)
	{
		if(!empty($dataArr))
        {
            $count = 1;
            $client = new Client();
            $ipArr = [];
            $ipCount = 0;
            $ipturn = 0;
            $run = 0;
            while (!empty($dataArr)) 
            {
                $data = array_chunk($dataArr, $cut);
                $total = count($dataArr);

                if ($total < $concurrency)
                {
                    $concurrency = $total;
                }

                foreach ($data as $key => $value) 
                {
                    if($useProxy)
                    {
                        if (empty($ipArr))
                        {
                            $ipArr = my_proxy(50);
                        }

                        $ipCount++;

                        if ($ipCount > (count($ipArr)-1))
                        {
                            $ipCount = 0;
                            $ipArr = [];
                            $ipArr = my_proxy(50);
                        }
                    }

                    $requests = function ($total) use ($client,$value,$config,$ipArr,$ipturn,$run, $useProxy) 
                    {
                        foreach ($value as $v) 
                        {
                            if ($useProxy)
                            {
                                //每组 2 个 元素用一个ip 循环
                                $run++;
                                if ($run > 2)
                                {
                                    $ipturn ++;
                                    $run = 0;
                                }
                                if ($ipturn > 49) $ipturn = 0;
                                $config['proxy'] = $ipArr[$ipturn];
                            }

                            $url = $v['url'];
                            $config['form_params'] = $v['params'];
                            yield function() use ($client,$url,$config) 
                            {
                                // print_r($config);dd();
                                return $client->postAsync($url,$config);
                            };
                        }
                    };
                    $pool = new Pool($client, $requests(count($value)), [
                        'concurrency' => $concurrency,
                        'fulfilled' => function ($response, $index) use($value) 
                        {     
                            // 校验回调成功
                            if($response->getStatusCode()==200)
                            {
                                $sContent = $response->getBody()->getContents();
                                // 保存文件 
                                if(!empty($sContent))
                                {
                                    existsOrCreate($value[$index]['file']);
                                    if ($ismin)
                                        file_put_contents($value[$index]['file'], minify($sContent));
                                    else
                                        file_put_contents($value[$index]['file'], $sContent);
                                    echo $value[$index]['title']." download successful!".PHP_EOL;
                                }
                            }
                        },
                        'rejected' => function ($reason, $index) use($value)
                        {
                            // echo ' windows ConnectException fail! '.PHP_EOL;
                        },
                    ]);
                    $promise = $pool->promise();
                    $promise->wait();
                }
                //循环次数  退出
                echo $count.' times finsh!!!'.PHP_EOL;
                $count++;

                //循环结束
                if ($count > $circle)
                {
                    return true;
                }
                
                //foreach 循环完毕 检漏
                foreach ($dataArr as $key => $value) 
                {
                    if (is_file($value['file'])) unset($dataArr[$key]);
                }
                sort($dataArr);

                $total = count($dataArr);

                if ($total < $concurrency)
                {
                    $concurrency = $total;
                }
                echo $total.' list !!!'.PHP_EOL;
            }
            return true;    
        }
        return true;
	}

    // 并发GET
    public function poolGet($dataArr,$cut=20,$concurrency=100,$circle=false,$useProxy = false,$config = [],$ismin = true)
    {
        if(!empty($dataArr))
        {
            $count = 1;
            $client = new Client();
            $ipArr = [];
            $ipCount = 0;
            $ipturn = 0;
            $run = 0;
            while (!empty($dataArr)) 
            {
                $data = array_chunk($dataArr, $cut);

                foreach ($data as $key => $value) 
                {
                    if($useProxy)
                    {
                        if (empty($ipArr))
                        {
                            $ipArr = my_proxy(50);
                        }

                        $ipCount++;

                        if ($ipCount > (count($ipArr)-1))
                        {
                            $ipCount = 0;
                            $ipArr = [];
                            $ipArr = my_proxy(50);
                        }
                    }

                    $requests = function ($total) use ($client,$value,$config,$ipArr,$ipturn,$run, $useProxy) 
                    {
                        foreach ($value as $v) 
                        {
                            if ($useProxy)
                            {
                                //每组 2 个 元素用一个ip 循环
                                $run++;
                                if ($run > 2)
                                {
                                    $ipturn ++;
                                    $run = 0;
                                }
                                if ($ipturn > 49) $ipturn = 0;
                                $config['proxy'] = $ipArr[$ipturn];
                            }

                            $url = $v['url'];
                            yield function() use ($client,$url,$config) 
                            {
                                return $client->getAsync($url,$config);
                            };
                        }
                    };
                    $pool = new Pool($client, $requests(count($value)), [
                        'concurrency' => $concurrency,
                        'fulfilled' => function ($response, $index) use($value,$ismin) 
                        {     
                            // 校验回调成功
                            if($response->getStatusCode()==200)
                            {
                                $sContent = $response->getBody()->getContents();
                                // 保存文件 
                                if(!empty($sContent))
                                {
                                    existsOrCreate($value[$index]['file']);
                                    if ($ismin)
                                        file_put_contents($value[$index]['file'], minify($sContent));
                                    else
                                        file_put_contents($value[$index]['file'], $sContent);
                                    echo $value[$index]['title']." download!".PHP_EOL;
                                }
                            }
                        },
                        'rejected' => function ($reason, $index) use($value)
                        {
                            // echo ' windows ConnectException fail! '.PHP_EOL;
                        },
                    ]);
                    $promise = $pool->promise();
                    $promise->wait();
                }
                //循环次数  退出
                echo $count.' times finsh!!!'.PHP_EOL;
                $count++;

                //循环结束
                if ($circle && $count > $circle)
                {
                    return true;
                }
                
                //foreach 循环完毕 检漏
                foreach ($dataArr as $key => $value) 
                {
                    if (is_file($value['file'])) unset($dataArr[$key]);
                }
                sort($dataArr);

                $total = count($dataArr);

                if ($total < $concurrency)
                {
                    $concurrency = $total;
                }
                echo $total.' list !!!'.PHP_EOL;
            }
            return true;    
        }
        return true;
    }
}