<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Insert title here</title>
 <script src="http://jquery.com/src/jquery-latest.js"></script>
 <script src="javascript/a3o.js"></script>
 <script type="text/javascript">
 	var tiles = new Array(<?php
		require_once 'config.php';
		require_once 'include/classes/Tile.php';

		try
		{
			$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);
		}
		catch (PDOException $e)
		{
			die($e->getMessage());
		}

		$tiles = Tile::loadGameTiles($pdo, 1);

		for($i = 0; $i < count($tiles); $i++)
		{
			echo $tiles[$i]->getTileAsJson() . ($i + 1 < count($tiles) ? ', ' : '');
		}
		
	?>);
 
   $(document).ready(function(){
     	/*$.getJSON('ajax.php', null, function(data) {
         	console.log(data);
    	    drawPolygon($("#map")[0].getContext('2d'), data, 'blue');
    	    
    	});*/
    	for (var i = 0; i < tiles.length; i++)
   	    {
   	    	var fill = null;
   	    	if (tiles[i].type == 1)
   	    	{
   	    		fill = 'blue';
   	    	}
   	    	else
   	    	{
   	    		fill = 'white';
   	    	}
   	    	var context = $("#map")[0].getContext('2d');
   	    	drawPolygon(context, tiles[i].vertices, fill);
   	    	context.fillStyle = 'black';
   	    	context.moveTo(tiles[i].center[0], tiles[i].center[1]);
   	    	context.beginPath();
   	    	context.arc(tiles[i].center[0], tiles[i].center[1], 5, 0, (Math.PI/180)*360, true);
   	    	context.fill();
   	    }
   });
 </script>

</head>
<body>
	<div id="result"></div>
	<canvas height="2278" width="4876" id="map">
	</canvas>
</body>
</html>