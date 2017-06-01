<!DOCTYPE HTML>
<html lang="zh-cmn-Hans">
<head>
	<meta charset="utf-8">
	<meta name="author" content="JJ Ying" />
	<title>Duoshuo2Hashover</title>
	<style>
		.comment {
			background: #f5f5f5;
			width: 50em;
			max-width: 90%;
			margin: 0 auto 1em;
			padding: .3em 1.5em 1.5em;
		}		
		.item {
			opacity: .5;
			font-size: .7em;
			line-height: .7;
		}
	</style>
</head>
<body>
	
	<?php
	
	//
	//这里要设置一下
	//
	$domainName = "http://Anyway.FM";   //网站域名
	
	header('content-type:text/html; charset=utf-8');
	ini_set('date.timezone', 'Asia/Shanghai');
	mkdir("comments/");
	
	$json = file_get_contents('export.json');
	
	$threadsAndPosts =  json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
	
	$postsID = array();
	
	foreach ($threadsAndPosts['posts'] as $v) {
		$postsID[] = $v['thread_id'];
	}
	
	foreach ($threadsAndPosts['threads'] as $threadsKey => $threadsValue) {
		if (empty($threadsValue['thread_key']) || !in_array($threadsValue['thread_id'], $postsID)) {
			unset($threadsAndPosts['threads'][$threadsKey]);
		}
	}
	
	foreach ($threadsAndPosts['posts'] as $k => $v) {
		if (!empty($v['parents'])) {
			$threadsAndPosts['posts'][$k]['parents'] = end($v['parents']);
		}
	}
	
	foreach($threadsAndPosts['threads'] as $threadsKey => $threadsValue) {
				
		// 处理 URL Alias
		$postTitleText  = substr($threadsValue['url'],(strlen($domainName)+1));
		$postTitleText = substr($postTitleText, 0, strripos($postTitleText,"/"));
		echo("<div class='comment'><h1>//".$postTitleText."</h1>");
		mkdir("comments/".$postTitleText);
		
		$c1 = 1;
	
		$newArray = array();
		$firstLevel =  array();
		$secondLevel = array();
		
		//处理第 1 层级评论
		foreach($threadsAndPosts['posts'] as $postsKey => $postValue) {
			
			if ($threadsValue['thread_id'] == $postValue['thread_id']) {
			
				
				if ($postValue['parents'] == "") {
					// ID、文件名、从属评论记数
					
					echo("<span class='item'><strong>".$postValue['author_name'].":</strong> ".$postValue['message']." ".$postValue['parents']."</span>");
					
					if ($postValue['author_email']) {
						$emailHash = md5($postValue['author_email']);
					}
					else {
						$emailHash = "";
					}
					
					$postJson = array(
					 	'body'=>$postValue['message'],
					 	'date'=>$postValue['created_at'],
					 	'name'=>$postValue['author_name'],
					 	'email'=>$postValue['author_email'],
					 	'encryption'=>"",
					 	'email_hash'=>$emailHash,
					 	'notifications'=>"no",
					 	'website'=>$postValue['author_url']
					 );
					$json_string = json_encode($postJson);
					
					$newArray = array($postValue['post_id'],$c1, 0);
					$firstLevel[] = $newArray;
					
					$postFileName = 'comments/'.$postTitleText.'/'.$c1.'.json';
					file_put_contents($postFileName, $json_string);
					$c1++;
					
					//Debug
					echo("<br />");
				}
				
			}
		}
	//	print_r($firstLevel);
	
		echo("<br /><h3>第 2 层评论</h3>");
		
		
		//处理第 2 层级评论
		foreach($threadsAndPosts['posts'] as $postsKey => $postValue) {
			$secondLevelNum = 1;
			if (($threadsValue['thread_id'] == $postValue['thread_id']) & $postValue['parents']) {
	
				$hasChildren = array_column($firstLevel, 0);
				$c2=0;
				foreach ($hasChildren as $key => $value) {
					if ($postValue['parents'] == $value) {
						
						$postJson = array(
						 	'body'=>$postValue['message'],
						 	'date'=>$postValue['created_at'],
						 	'name'=>$postValue['author_name'],
						 	'email'=>$postValue['author_email'],
						 	'encryption'=>"",
						 	'email_hash'=>md5($postValue['author_email']),
						 	'notifications'=>"no",
						 	'website'=>$postValue['author_url']
						 );
						$json_string = json_encode($postJson);
						
						echo("<span class='item'><strong>".$postValue['author_name'].":</strong> ".$postValue['message']." "."</span><br />");
						
						
						$newArray = array($postValue['post_id'],$firstLevel[$c2][1]."-".$secondLevelNum, 0);
						$secondLevel[] = $newArray;
						
						
						$postFileName = 'comments/'.$postTitleText.'/'.$firstLevel[$c2][1]."-".$secondLevelNum.'.json';
						file_put_contents($postFileName, $json_string);
						
	
					}
					$c2++;
				}
				
				$secondLevelNum ++;
	
			}
		}
		
	
		echo("<br /><h3>第 3 层评论</h3>");
		
			//处理第 3 层级评论
		foreach($threadsAndPosts['posts'] as $postsKey => $postValue) {
			$thirdLevelNum = 1;
			if (($threadsValue['thread_id'] == $postValue['thread_id']) & $postValue['parents']) {
	
				$hasChildren = array_column($secondLevel, 0);
				$c3=0;
				foreach ($hasChildren as $key => $value) {
					if ($postValue['parents'] == $value) {
						
						$postJson = array(
						 	'body'=>$postValue['message'],
						 	'date'=>$postValue['created_at'],
						 	'name'=>$postValue['author_name'],
						 	'email'=>$postValue['author_email'],
						 	'encryption'=>"",
						 	'email_hash'=>md5($postValue['author_email']),
						 	'notifications'=>"no",
						 	'website'=>$postValue['author_url']
						 );
						$json_string = json_encode($postJson);
						
						echo("<span class='item'><strong>".$postValue['author_name'].":</strong> ".$postValue['message']."</span><br />");
						
						$postFileName = 'comments/'.$postTitleText.'/'.$secondLevel[$c3][1]."-".$thirdLevelNum.'.json';
						file_put_contents($postFileName, $json_string);
						
	
					}
					$c3++;
				}
				
				$thirdLevelNum ++;
				
				
			}
		}
		
		echo("</div>");
	}
	
	
		
	
	?>
</body>
</html>