<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Insert title here</title>
 <script src="http://jquery.com/src/jquery-latest.js"></script>
 <script src="javascript/a3o.js"></script>
 <script src="javascript/a3oRenderer.js"></script>
 <script type="text/javascript">
 	var tiles = {<?php
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
		$tileFactory->precacheTiles();
		$tileFactory->precacheNeighbours();
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
			echo '"' . $tile->getName() . '":' . $tile->getTileAsJson();
		}
		
	?>};

 	var renderer = null;
	var lastClick = null;
	var pathfindindSuspended = false;
	var cleanImage = null;
	var beginTranslation = [ 0, 0 ];
	var tempTranslation = [ 0, 0 ];
	var translating = false;
 
   $(document).ready(function(){
		$(document).bind("contextmenu", function(e){
			return false;
		});
	    var context = $("#map")[0].getContext('2d');

	    renderer = new Renderer(context);

    	renderer.newLayer( );
    	renderer.newLayer( );
    	renderer.newLayer( );
    	/*for (tile in tiles)
    	{
    		renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'white' ), "tile_" + tile );
    	}*/

		var image;
    	
		for( var i = 0; i < 9; i++ )
		{
			for ( var j = 0; j < 20; j++)
			{
				image = new Image( 256, 256 );
				image.onload = function() {
					//console.log('Loaded ' + this.src + '.png');
					renderer.layers[0].addDrawable( new Sprite( this, this.j * 256, this.i * 256 ), this.j + '_' + this.i + '.png' );
				}
				image.i = i;
				image.j = j;
				image.src = '/A3O/images/games/big_world/baseTiles/' + j + '_' + i + '.png';
			}
		}
    	
    	renderer.layers[2].addDrawable( new Polygon( tiles["Afghanistan"].vertices, 0, 0, 'red', 'transparent' ), "mouse_highlighted" );
 	    renderer.startRendering();
    	
    	cleanImage = context.getImageData(0, 0, $("#map")[0].width, $("#map")[0].height);

		$('#map').mousedown( function(e) {
			var x = e.pageX - this.offsetLeft;
   	    	var y = e.pageY - this.offsetTop;
   	    	if (e.which == 3)
   	    	{
   	   	    	//console.log('mousedown');
   	   	    	if (!translating)
   	   	    	{
   	    			translating = true;
   	   	    		beginTranslation = [ x, y ];
   	   	  			tempTranslation = renderer.viewPortOffset.slice(0);
   	   	    	}
   	    	}
		});

		$('#map').mouseup( function(e) {
   	    	if (e.which == 3)
   	    	{
   	    		translating = false;
   	    		//console.log('mouseup');
   	    	}
		});

		$('#map').mouseleave( function(e) {
   	    	//console.log('mouseleave');
    		translating = false;

		});
    	
   	    $('#map').click( function (e) {
   	    	var context = $("#map")[0].getContext('2d');
   	 		var x = e.pageX - this.offsetLeft + renderer.viewPortOffset[0];
    		var y = e.pageY - this.offsetTop + renderer.viewPortOffset[1];
   	    	
   	    	var candidates = new Array();
   	    	
   	    	for(var name in tiles)
   	    	{
   	    		if ( (x > tiles[name].boundingbox.upperleft[0])
   	    			&& (x < tiles[name].boundingbox.lowerright[0])
   	    			&& (y > tiles[name].boundingbox.upperleft[1])
   	    		 	&& (y < tiles[name].boundingbox.lowerright[1]) )
   	    		{ 
   	    			candidates.push(tiles[name]);
   	    		}
   	    	}
   	    	
   	    	var found = null;
   	    	
   	    	if(candidates.length == 1)
   	    	{
   	    		found = candidates[0];
   	    	}
   	    	else if(candidates.length > 1)
   	    	{
   	    		for(var i = 0; i < candidates.length; i++)
   	    		{
   	    			if (isPointInPolygon(candidates[i].vertices, x, y))
   	    			{
   	    				found = candidates[i];
   	    				break;
   	    			}
   	    		}
   	    	}
   	    	
   	    	
   	    	if (found != null)
   	    	{
				if (lastClick == null)
				{
					//drawTiles($("#map")[0].getContext('2d'),tiles);
					lastClick = found.name;
					renderer.layers[1].clearDrawables();
				}
				else
				{
	  				var arr = findPath(tiles, lastClick, found.name, 2, 10);
	 	    		if (arr)
	 	    		{
			  	    	for(var i = 0; i < arr.length; i++)
			  	    	{
			  	    		//console.log('pathfinding tracking: ' + arr[i]);
			  	    	}
	  	   			 }
	  	   			 else
	  	   			 {
	  	   				//console.log('no path found');
			  	     }
	  	    		 lastClick = null;
	  			}
	  			renderer.layers[1].addDrawable ( new Polygon( found.vertices, 0, 0, 'red', 'transparent' ), "tile_" + found.name );
   	    	}

   	    });
   	 	$('#map').mousemove( function (e) {

   	 		var x = e.pageX - this.offsetLeft;
    		var y = e.pageY - this.offsetTop;
    		
			if (translating)
			{
				//console.log('x:\t' + x + '\t\ty:\t' + y); 
				//console.log('t_x:\t' + tempTranslation[0] + '\t\tt_yy:\t' + tempTranslation[1]); 
				renderer.viewPortOffset[0] = tempTranslation[0] + (beginTranslation[0] - x);
				if ( renderer.viewPortOffset[0] < 0 )
				{
					renderer.viewPortOffset[0] = 0;
				}
				renderer.viewPortOffset[1] = tempTranslation[1] + (beginTranslation[1] - y);
				if (renderer.viewPortOffset[1] < 0)
				{
					renderer.viewPortOffset[1] = 0;
				}
				renderer.layers[0].invalidate();
			}

			x = e.pageX - this.offsetLeft + renderer.viewPortOffset[0];
    		y = e.pageY - this.offsetTop + renderer.viewPortOffset[1];;
   	   	 	
   	   	 	if ( pathfindindSuspended )
   	   	 	{
   	   	   	 	return;
   	   	 	}
   	 		if (lastClick != null)
			{
   				pathfindingSuspended = true;
	   	 		var context = $("#map")[0].getContext('2d');
		    	
		    	var candidates = new Array();
		    	
		    	for(var name in tiles)
		    	{
		    		if ( (x > tiles[name].boundingbox.upperleft[0])
		    			&& (x < tiles[name].boundingbox.lowerright[0])
		    			&& (y > tiles[name].boundingbox.upperleft[1])
		    		 	&& (y < tiles[name].boundingbox.lowerright[1]) )
		    		{ 
		    			candidates.push(tiles[name]);
		    		}
		    	}
		    	
		    	var found = null;
		    	
		    	if(candidates.length == 1)
		    	{
		    		found = candidates[0];
		    	}
		    	else if(candidates.length > 1)
		    	{
		    		for(var i = 0; i < candidates.length; i++)
		    		{
		    			if (isPointInPolygon(candidates[i].vertices, x, y))
		    			{
		    				found = candidates[i];
		    				break;
		    			}
		    		}
		    	}

		    	if (found != null)
		    	{
			    	renderer.layers[2].drawables["mouse_highlighted"].vertices = found.vertices;
			    	renderer.layers[2].drawables["mouse_highlighted"].invalidate();
		    		var arr = findPath(tiles, lastClick, found.name, 2, 10);
	 	    		if (arr)
	 	    		{
		 	    		var centers = new Array( );
		 	    		
			  	    	for(var i = 0; i < arr.length; i++)
			  	    	{
			  	    		centers.push( tiles[arr[i]].center );
			  	    		//console.log( tiles[arr[i]].center );
			  	    	}
			  	  		renderer.layers[2].addDrawable( new Path( centers, 'red', 10, 'black'), "path");
	  	   			 }
	  	   			 else
	  	   			 {
			  	    	//console.log('no path found');
			  	    }
		    	}
		    	pathfindingSuspended = false;
  			}   	 		
   	 	});
   });
 </script>

</head>
<body>
	<div id="result"></div>
	<canvas height="625" width="1250" id="map" style="border:solid black;">
	</canvas>
</body>
</html>