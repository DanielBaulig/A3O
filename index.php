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
 			
		try
		{
			$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password );
		}
		catch (PDOException $e)
		{
			die($e->getMessage());
		}
		if ( ! $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) || $pdo->getAttribute(PDO::ATTR_ERRMODE) != PDO::ERRMODE_EXCEPTION)
		{
			die ('Error Mode change failed!');
		}

		//$pdo->query('lol');
		
		require_once 'include/classes/Tile.php';
		require_once 'include/classes/Unit.php';
		
		//die ('lol ' . ($pdo->getAttribute( PDO::ATTR_ERRMODE ) == PDO::ERRMODE_EXCEPTION));

		$unitTypeRegistry = new UnitTypeRegistry( $pdo, 1 );
		$unitType = $unitTypeRegistry->getElement( 2 );
		
		$tileFactory = new BaseTileRegistry( $pdo, 1 );
		$tileFactory->precacheElements();
		//$tileFactory->precacheNeighbours();
		$tiles = $tileFactory->getAllElements();

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
	var FPSDrawable = null;
 
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

	    renderer = new Renderer(context, 4876, 2278);

    	renderer.newLayer( );
    	renderer.newLayer( );
    	renderer.newLayer( );

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

		/*var sum = 0;
		var clearedSum = 0;
		for ( var tile in tiles ) 
		{
			var oldl = tiles[tile].vertices.length;
			tiles[tile].vertices = DouglasPecker ( tiles[tile].vertices, 0 );
			var newl = tiles[tile].vertices.length;
			sum = sum + oldl;
			clearedSum = clearedSum + oldl - newl;
		}
		alert ( clearedSum/sum );*/
		
		for ( var tile in tiles )
		{
			if ( tiles[tile].owner == 0 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'orange' ), tile );
			}
			if ( tiles[tile].owner == 1 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'red' ), tile );
			}
			if ( tiles[tile].owner == 2 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'grey' ), tile );
			}
			if ( tiles[tile].owner == 3 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'yellow' ), tile );
			}
			if ( tiles[tile].owner == 4 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'green' ), tile );
			}
			if ( tiles[tile].owner == 5 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'tan' ), tile );
			}
			if ( tiles[tile].owner == 6 )
			{
				renderer.layers[0].addDrawable( new Polygon( tiles[tile].vertices, 0, 0, 'black', 'purple' ), tile );
			}
		}
    	
    	renderer.layers[2].addDrawable( new Polygon( null, 0, 0, 'red', 'transparent' ), "mouse_highlighted" );
    	//renderer.layers[2].addDrawable( FPSDrawable = new Text( 'fps: ', 'italic 400 24px/2 Unknown Font, sans-serif', 50, 50, 'red', 'black' ), "fps" );
 	    renderer.startRendering( );
    	
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

   	    			document.body.style.cursor='move';
   	    			
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

   	    		document.body.style.cursor='default';
   	    		
   	    		renderer.resumeBuffering( );
   	    		//console.log('mouseup');
   	    	}
		});

		$('#map').mouseleave( function(e) {
   	    	//console.log('mouseleave');
   	    	
   	    	document.body.style.cursor='default';
   	    	
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
				offset[1] = tempTranslation[1] + (beginTranslation[1] - y);

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
		    		renderer.layers[2].drawables["mouse_highlighted"].setVertices ( found.vertices );
			    	//renderer.layers[2].drawables["mouse_highlighted"].invalidate();
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
	<div id="result"><?php print_r( $unitType ); ?></div>
	This is a development version of 'A3O', a browser based Tripple A / Axis & Allies clone. It is for testing only.<br/>
	<canvas height="625" width="1250" id="map" style="border:solid black;">
		You need HTML5 Canvas to view this webpage. You can get a HTML5 capable browser <a href="http://www.google.com/chrome/">here</a>.
	</canvas><br />
	mouswheel: zoom map<br />
	right mousbutton: pan map<br />
	left mousebutton: find path<br /> 
	All assets and static ressources found on this webpage are not part of this project but originate from the TripleA project and are licenced under GNU GPL.<br /> 
	Please refer to <a href="http://triplea.sourceforge.net">http://triplea.sourceforge.net<a/> for more information on the TripleA project. 
</body>
</html>