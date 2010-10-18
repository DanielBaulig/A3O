A3O = function () { 
	// CONSTANTS
	const GAME_NAME = 'big_world';
	const BOARD_WIDTH = 4876;
	const BOARD_HEIGHT = 2278;
	
	// HELPER FUNCTIONS
	var createCallback = function (limit, fn) {
		var finishedCalls = 0;
		return function() {
			if (++finishedCalls == limit) {
				fn();
	        }
	    };
	}
	
	var isPointInPolygon = function (vertices, x, y) {
		var i, j, c = false;
		for (i = 0, j = vertices.length-1; i < vertices.length; j = i++) {
			if ( ( (vertices[i][1]>y ) != (vertices[j][1]>y) ) &&
				(x < (vertices[j][0]-vertices[i][0]) * (y-vertices[i][1]) / (vertices[j][1]-vertices[i][1]) + vertices[i][0]) ) {
				c = !c;
			}
		}
		return c;
	}
	
	
	// A3O GAME OBJECT
	return {
		panning: false,
		panningStart: {x:0,y:0},
		viewportOffset: {x:0,y:0},
		viewportOffsetPanningStart: {x:0,y:0},
		ressources: {},
		drawBoard: function( saveBuffer ) {
			this.drawZonePolygons();
			this.drawBackgroundImages();
			this.drawUnits();			
			if ( saveBuffer ) {
				//this.boardBuffer.src = this.bufferContext.canvas.toDataURL('image/png');
				this.boardBufferData = this.bufferContext.getImageData( 0, 0, BOARD_WIDTH, BOARD_HEIGHT );
			}
			if (console)
				console.log('drawBoard();');
		},
		drawUnits: function( ) {
			
		},
		drawInterface: function( useBuffer ) {
			var bufferContext = this.bufferContext;
			if ( useBuffer ) {
				bufferContext.putImageData( this.boardBufferData, 0, 0 );
				//bufferContext.drawImage ( this.boardBuffer, 0, 0, BOARD_WIDTH, BOARD_HEIGHT );
				//this.drawBoard( false );
			}
			// draw selected zone
			if ( this.selectedZone ) {
				var polygon = this.ressources.polygons[this.selectedZone].polygon;
				var length = polygon.length;
				
				bufferContext.save();
				bufferContext.strokeStyle = 'black';
				bufferContext.lineWidth = 10;
				bufferContext.fillStyle = 'red';
				bufferContext.shadowOffsetX = 0;
				bufferContext.shadowOffsetY = 0;
				bufferContext.shadowOffsetY = 0;
				bufferContext.shadowBlur = 10;
				bufferContext.shadowColor = 'red';
				
				bufferContext.beginPath();
				
				bufferContext.moveTo( polygon[0][0], polygon[0][1] );
				
				for( var i = 1; i < length; i++ ) {
					bufferContext.lineTo( polygon[i][0], polygon[i][1] );
				}
				
				bufferContext.closePath();
				bufferContext.stroke();
				bufferContext.fill();
				
				bufferContext.restore();
			}
		},
		drawBackgroundImages: function ( ) {
			var images = this.ressources.backgroundImages;
		
			for(var image in images) {
				image = images[image];
				this.bufferContext.drawImage(image.image, image.x, image.y);
			}	
		},
		drawZonePolygons: function( ) {
			var polygons = this.ressources.polygons;
			var bufferContext = this.bufferContext;
			var i;
			bufferContext.save();
			bufferContext.strokeStyle = 'black';
			bufferContext.lineWidth = 1;
			bufferContext.fillStyle = 'pink';
			c = 0;
			
			for (var polygon in polygons) {
				polygon = polygons[polygon];
				
				// do something with the entire polygon structure
				// ...
				if (polygon.sz){
					continue;
				}
				
				// get the vertex list
				polygon = polygon.polygon;
				var length = polygon.length;
				bufferContext.beginPath();
				if (length > 0) {
					bufferContext.moveTo( polygon[0][0], polygon[0][1] );	
				}			
				for(var i = 1; i < length; i++){
					var point = polygon[i];
					bufferContext.lineTo( point[0], point[1] );
					c++;
				}
				
				bufferContext.closePath();
				bufferContext.fill();
				bufferContext.stroke();
			}
			bufferContext.restore();
			if (console)
				console.log (c);
		},
		swapBuffers: function( ) {
			var viewportOffsetX = this.viewportOffset.x;
			var viewportOffsetY = this.viewportOffset.y;
			var viewportCanvasWidth = this.viewportContext.canvas.width;
			var viewportCanvasHeight = this.viewportContext.canvas.height;
			this.viewportContext.drawImage(
				this.bufferContext.canvas, 
				viewportOffsetX, 
				viewportOffsetY, 
				viewportCanvasWidth, 
				viewportCanvasHeight, 
				0, 
				0,
				viewportCanvasWidth, 
				viewportCanvasHeight 
			);
			// if the viewport is past the right edge 
			if (viewportOffsetX > (BOARD_WIDTH - viewportCanvasWidth) )
			{
				this.viewportContext.drawImage(
					this.bufferContext.canvas, 
					0,
					viewportOffsetY, 
					viewportCanvasWidth, 
					viewportCanvasHeight, 
					-(viewportOffsetX - BOARD_WIDTH),
					0,
					viewportCanvasWidth, 
					viewportCanvasHeight 
				);
			}
		},
		loadRessources: function ( doneCallback ) {
			var delayedCallback = createCallback( 3, doneCallback );
			this.loadPolygons( delayedCallback );
			this.loadBackgroundImages( delayedCallback );
			this.loadSprites( delayedCallback );
		},
		loadSprites: function ( doneCallback ) {
			doneCallback( );
		},
		loadPolygons: function( doneCallback ) {
			var that = this;
			jQuery.getJSON('static/json/games/'+GAME_NAME+'/polygons.json', function( data ) {
					that.ressources.polygons = data;
					for(var polygon in that.ressources.polygons) {
						polygon = that.ressources.polygons[polygon];
						// HACK
						if (polygon.name.match(/^SZ/))
						{
							polygon.sz = true;
						}
						// HACK END
					}
					doneCallback( );
				}
			);
		},
		loadBackgroundImages: function( doneCallback ) {
			var i = 0, j = 0, imageName = '';
			var delayedCallback = createCallback( (20 * 9) - 10, doneCallback );
			var that = this;
			
			this.ressources.backgroundImages = [];
			for( i = 0; i < 20; i++ ) {
				for( j = 0; j < 9; j++ ) {
					imageName = i + '_' + j + '.png';
					switch(imageName) {
						// those pictures don't exist, no need to try to load them
						case '10_0.png':
						case '10_1.png':
						case '10_3.png':
						case '11_0.png':
						case '18_2.png':
						case '19_1.png':
						case '19_2.png':
						case '19_3.png':
						case '9_1.png':
						case '9_3.png':
							continue;
					}
					var image = new Image( );
					image.onload = (function( ) {
						// close over those variables
						var c_i = i, c_j = j, c_image = image;
						// return a specialized callback using the enclsoed variables
						return function ( ) { 
							// now safe the image after it was loaded; use the specialized (enclosed) varibales!
							that.ressources.backgroundImages.push( { image: c_image, x: c_image.width * c_i, y: c_image.height * c_j } );
							delayedCallback( );
						}
					}) ( ); // invoke the closure that returns the specialized callback
					image.src = 'static/images/games/'+GAME_NAME+'/baseTiles/'+imageName;
				}				
			}
		},
		transformCoordinates: function (x, y) {
			var offset = jQuery(this.viewportContext.canvas).offset();
			x = x + this.viewportOffset.x - offset.left;
			if ( x > BOARD_WIDTH ) {
				x -= BOARD_WIDTH;
			}				
			y = y + this.viewportOffset.y + - offset.top;
			return {x: x, y: y};
		},
		selectZone: function (x, y) {
			var polygons = this.ressources.polygons;
			var coords = this.transformCoordinates(x, y);
			x = coords.x;
			y = coords.y;
			if (console)
				console.log('X: ' + x + ' Y: ' + y);
			for(var p in polygons) {
				// store some local variables to speed things up
				polygon = polygons[p];
				var ul = polygon.boundingbox.ul;
				var lr = polygon.boundingbox.lr;
	
				if ( (ul[0] < x) && (lr[0] > x) ) {
					if ( (ul[1] < y) && (lr[1] > y) )
					{
						if ( isPointInPolygon(polygon.polygon, x, y)) {
							if (console)
								console.log(polygon.name);
							this.selectedZone = p;
							return;
						}
					}
				}				
			}
		},
		startPanning: function (x, y) {
			this.panning = true;
			this.panningStart.x = x;
			this.panningStart.y = y;
			this.viewportOffsetPanningStart.x = this.viewportOffset.x;
			this.viewportOffsetPanningStart.y = this.viewportOffset.y;
		},
		stopPanning: function(x, y) {
			this.panning = false;
		},
		pan: function(x, y) {
			this.viewportOffset.x = this.viewportOffsetPanningStart.x - ( x - this.panningStart.x );
			this.viewportOffset.y = this.viewportOffsetPanningStart.y - ( y - this.panningStart.y );
			if ( this.viewportOffset.y < 0 ) {
				this.viewportOffset.y = 0;
			}
			if( this.viewportOffset.y > (this.bufferContext.canvas.height - this.viewportContext.canvas.height) ) {
				this.viewportOffset.y = this.bufferContext.canvas.height - this.viewportContext.canvas.height;
			}
			if( this.viewportOffset.x < 0 ) {
				this.viewportOffset.x = BOARD_WIDTH;
				this.startPanning( x, y );
			}
			if( this.viewportOffset.x > BOARD_WIDTH ) {
				this.viewportOffset.x = 0;
				this.startPanning( x, y );
			}	
		},
		setup: function( viewportContext ) {
			var bufferCanvas = document.createElement('canvas');
			this.boardBuffer = new Image ( );
			bufferCanvas.width = BOARD_WIDTH;
			bufferCanvas.height = BOARD_HEIGHT;
			this.bufferContext = bufferCanvas.getContext('2d');
			this.viewportContext = viewportContext;
			var that = this;
	
			jQuery(this.viewportContext.canvas).mousedown( function( e ) {
				switch( e.which ) {
					case 3:
						that.startPanning( e.screenX, e.screenY );
				}
			});
			
			jQuery(this.viewportContext.canvas).mouseup( function( e ) {
				switch( e.which ) {
					case 3:
						that.stopPanning( );			
				}
			});
			
			jQuery(this.viewportContext.canvas).mousemove( function( e ) {
				if (that.panning) {
					that.pan( e.screenX, e.screenY );
					that.swapBuffers();
				}
			});
			
			jQuery(this.viewportContext.canvas).click( function( e ) {
				switch( e.which ) {
					case 1:
						that.selectZone( e.pageX, e.pageY );
						that.drawInterface( true );
						that.swapBuffers( );
				}
			});
			
			jQuery(this.viewportContext.canvas).bind( 'contextmenu', function( e ) {
				return false;
			});
			
			this.drawBoard( true );
			this.swapBuffers();
		}
	};

} ();

