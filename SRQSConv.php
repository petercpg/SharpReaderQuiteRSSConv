<?php
	if (!isset($_GET['file']) && !isset($_GET['searchcurrdir'])) {
		die('Missing Filename');
	} elseif (isset($_GET['file']) && isset($_GET['searchcurrdir'])) {
		die('You can only set filename or currdir');
	}
	
	if ($_GET['searchcurrdir'] == true) {
		foreach (glob("*.xml") as $file) {
    		convertXMLFeeds($file);
		}
	}
	
	if (isset($_GET['file'])) {
		if (substr($_GET['file'], -4) == '.xml') {
			$file = $_GET['file'];
		} else {
			$file = $_GET['file'].'.xml';
		}
		if (file_exists($file)) {
			convertXMLFeeds($file);
		} else {
			die('Missing File');
		}
	}
	
	function convertXMLFeeds($file) {
		$db = new SQLite3('feeds.db');
		$xml = simplexml_load_file($file);
		
		//拿鎖定的文章內容
		$posts = array();
		$i = 0;
		foreach($xml->children() as $rss) {
			//if ($rss->Title === "") continue;
			//if ($rss->IsDeleted === true) continue;
			if (!$rss->IsLocked === true) continue;
			$posts[$i]["WebPageUrl"] = (string)$xml->WebPageUrl;
			$posts[$i]["Title"] = (string)$rss->Title;
			$posts[$i]["Link"] = (string)$rss->Link;
			$posts[$i]["Description"] = (string)$rss->Description;
			$posts[$i]["PubDate"] = (string)$rss->PubDate;
			$posts[$i]["Subject"] = (string)$rss->Subject;
			$posts[$i]["Author"] = (string)$rss->Author;
			$posts[$i]["Guid"] = (string)$rss->Guid;
			$i++;
		}
		if (count($posts) == 0) {
			return 'No Locked Posts.';
		}
		
		//找 Feed ID
		$sql = 'SELECT id FROM feeds WHERE htmlUrl = "' . $xml->WebPageUrl . '";';
		$results = $db->query($sql);
		$feedId = 0;
		while ($row = $results->fetchArray()) {
			$feedId = $row['id'];
			if ($feedId == 0) die('feedId error');
		}

		$pointer = 0;
		foreach($posts as $j) {
			$stmt = $db->prepare('INSERT INTO news (feedId, guid, description, content, title, published, received, author_name, category, read, starred, deleted, link_href) VALUES (:feedId,:Guid,:Description,:Description2,:Title,:PubDate,:PubDate2,:Author,:Subject,0,1,0,:Link);');
			$stmt->bindValue(':feedId', $feedId, SQLITE3_INTEGER);
			$stmt->bindValue(':Guid', $posts[$pointer]['Guid'], SQLITE3_TEXT);
			$stmt->bindValue(':Description', $posts[$pointer]['Description'], SQLITE3_TEXT);
			$stmt->bindValue(':Description2', $posts[$pointer]['Description'], SQLITE3_TEXT);
			$stmt->bindValue(':Title', $posts[$pointer]['Title'], SQLITE3_TEXT);
			$stmt->bindValue(':PubDate', $posts[$pointer]['PubDate'], SQLITE3_TEXT);
			$stmt->bindValue(':PubDate2', $posts[$pointer]['PubDate'], SQLITE3_TEXT);
			$stmt->bindValue(':Author', $posts[$pointer]['Author'], SQLITE3_TEXT);
			$stmt->bindValue(':Subject', $posts[$pointer]['Subject'], SQLITE3_TEXT);
			$stmt->bindValue(':Link', $posts[$pointer]['Link'], SQLITE3_TEXT);
			$insert = $stmt->execute();
			if ($db->changes()) {
				echo $db->changes() . " Records updated.\n";
			} else {
				echo "None Updated.\n";
			}
			$pointer++;
		}
		echo " DB Close.\n";
		$db->close();
	}
?>