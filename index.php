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

		$tileFactory = new TileFactory($pdo, 1);
		$tiles = $tileFactory->getAllTiles();

		$first = true;

		foreach($tiles as &$tile)
		{
			if ( $first )
			{
				$first = false;
			}
			else
			{
				echo ',';
			}
			echo $tile->getTileAsJson();
		}
		
	?>);
 
   $(document).ready(function(){
   		var context = $("#map")[0].getContext('2d');
    	for (var i = 0; i < tiles.length; i++)
   	    {   	    	
   	    	drawTile(context, tiles[i]);
   	    }
   	    
   	    
   	    $('#map').click( function (e) {
   	    	var context = $("#map")[0].getContext('2d');
   	    	var x = e.pageX - this.offsetLeft;
   	    	var y = e.pageY - this.offsetTop;
   	    	
   	    	var candidates = new Array();
   	    	
   	    	for(var i = 0; i < tiles.length; i++)
   	    	{
   	    		if (x > tiles[i].boundingbox.upperleft[0]
   	    			&& x < tiles[i].boundingbox.lowerright[0]
   	    			&& y > tiles[i].boundingbox.upperleft[1]
   	    		 	&& y < tiles[i].boundingbox.lowerright[1])
   	    		{ 
   	    			candidates.push(tiles[i]);
   	    		}
   	    	}
   	    	
   	    	if(candidates.length == 1)
   	    	{
   	    		drawPolygon(context, candidates[0].vertices, null, 'red');
   	    	}
   	    	else if(candidates.length > 1)
   	    	{
   	    		for(var i = 0; i < candidates.length; i++)
   	    		{
   	    			if (isPointInPolygon(candidates[i].vertices, x, y))
   	    			{
   	    				drawPolygon(context, candidates[i].vertices, null, 'red');
   	    				break;
   	    			}
   	    		}
   	    	}
   	    });
   });
 </script>

</head>
<body>
	<div id="result"></div>
	<canvas height="2278" width="4876" id="map">
	</canvas>
</body>
</html>