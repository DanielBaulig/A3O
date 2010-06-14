<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Insert title here</title>
 <script src="http://jquery.com/src/jquery-latest.js"></script>
 <script src="javascript/jQuery_mousewheel_plugin.js"></script>
 <script src="javascript/a3o.js"></script>
 <script src="javascript/a3oRenderer.js"></script>
 <script type="text/javascript">
 	var tiles = {<?php
		require_once 'config.php';
		require_once 'include/classes/Tile.php';
		require_once 'include/classes/Unit.php';

		try
		{
			$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);
		}
		catch (PDOException $e)
		{
			die($e->getMessage());
		}

		//$unitTypeFactory = new UnitTypeFactory( $pdo, 1 );
		//$unitType = $unitTypeFactory->getUnitType( 'infantry' );
		
		
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
	var zoom = 1.0;
 
   $(document).ready(function(){
	   	$("#map").mousewheel(function( sender, delta ){
		   	zoom += delta * 0.25;
		   	if ( zoom < 0.5 )
		   	{
			   	zoom = 0.5;
		   	}
		   	else if ( zoom > 1 )
		   	{
			   	zoom = 1;
		   	}
		   	renderer.setViewportZoom ( zoom );
	   	}, true );
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

		for ( var tile in tiles )
		{
			if ( tiles[tile].owner == 1 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'red' ), tile );
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
   	    			// increase redraw rate by supending layer buffering
   	    			renderer.suspendBuffering( );
   	   	    		beginTranslation = [ x, y ];
   	   	  			tempTranslation = renderer.viewPortOffset.slice(0);
   	   	    	}
   	    	}
		});

		$('#map').mouseup( function(e) {
   	    	if (e.which == 3)
   	    	{
   	    		translating = false;
   	    		renderer.resumeBuffering( );
   	    		//console.log('mouseup');
   	    	}
		});

		$('#map').mouseleave( function(e) {
   	    	//console.log('mouseleave');
   	    	renderer.resumeBuffering( );
    		translating = false;

		});
    	
   	    $('#map').click( function (e) {
   	    	var context = $("#map")[0].getContext('2d');
   	    	var coords = renderer.getWorldCoordinates( e.pageX, e.pageY );
   	 		var x = coords[0];
    		var y = coords[1];
   	    	
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
				var offset = [ 0, 0 ]; 
				offset[0] = tempTranslation[0] + (beginTranslation[0] - x);
				if ( offset[0] < 0 )
				{
					offset[0] = 0;
				}
				offset[1] = tempTranslation[1] + (beginTranslation[1] - y);
				if (offset[1] < 0)
				{
					offset[1] = 0;
				}

				renderer.setViewportOffset( offset );
			}

			var coords = renderer.getWorldCoordinates ( e.pageX, e.pageY );
			
			x = coords[0];
    		y = coords[1];;
   	   	 	
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
			  	    	//centers.pop();
			  	    	//centers.push( [x,y] );
			  	    	renderer.layers[2].addDrawable( new Path( centers, 'red', 5, 'black'), "path");
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