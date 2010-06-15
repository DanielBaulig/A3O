var Drawable = function( x, y )
{
	this.position = [x, y];
	this.layer = null;
	
	this.render = function( context ) 
	{ 
	};
	
	this.setLayer = function ( layer )
	{
		this.layer = layer;
	};
	
	this.invalidate = function( )
	{
		this.layer.invalidate();
	};
};

var Sprite = function( image, x, y )
{
	this.super = Drawable;
	this.super(x, y);
	this.image = image;
	this.render = function( context )
	{
		context.drawImage( this.image, this.layer.renderer.wrapCoordinatesX( this.position[0] ), this.position[1] );
	};
};

var Path = function ( points, color, lineWidth, shadowColor )
{
	if (points != null && (points.length > 1))
	{
		this.points = points;
		
		this.boundingBox = { upperLeft: this.points[0].slice(), lowerRight : this.points[0].slice() };
		for ( var i = 1; i < this.points.length; i++ )
		{
			if ( this.points[i][0] > this.boundingBox.lowerRight[0] )
			{
				this.boundingBox.lowerRight[0] = this.points[i][0];
			}
			if ( this.points[i][1] > this.boundingBox.lowerRight[1] )
			{
				this.boundingBox.lowerRight[1] = this.points[i][1];
			}
			if ( this.points[i][0] < this.boundingBox.upperLeft[0] )
			{
				this.boundingBox.upperLeft[0] = this.points[i][0];
			}
			if ( this.points[i][1] < this.boundingBox.upperLeft[1] )
			{
				this.boundingBox.upperLeft[1] = this.points[i][1];
			}
		}
		
		// unmapped control points - not needed atm
		/*
		this.controlpoints = new Array();
		this.controlpoints.push( this.points[0] );
		for (var i = 1; i < this.points.length - 1; i++)
		{
			var ab = [ this.points[i][0] - this.points[i-1][0], this.points[i][1] - this.points[i-1][1] ];
			var ab_mid = [ this.points[i-1][0] + Math.floor(ab[0] / 2), this.points[i-1][1] + Math.floor(ab[1] / 2) ];
			var ab_len = Math.sqrt(ab[0]*ab[0] + ab[1]*ab[1]);
			var bc = [ this.points[i+1][0] - this.points[i][0], this.points[i+1][1] - this.points[i][1] ];
			var bc_mid = [ this.points[i][0] + Math.floor(bc[0] / 2), this.points[i][1] + Math.floor(bc[1] / 2) ];
			var bc_len = Math.sqrt( bc[0]*bc[0] + bc[1]*bc[1] );
			var ab_mid_bc_mid = [ bc_mid[0] - ab_mid[0], bc_mid[1] - ab_mid[1] ];
			var ab_mid_bc_mid_len = Math.sqrt( ab_mid_bc_mid[0]*ab_mid_bc_mid[0] + ab_mid_bc_mid[1]*ab_mid_bc_mid[1] );
			var ratio =  ab_mid_bc_mid_len / (ab_len + bc_len);
			
			var mid_mid = [ Math.floor(ab_mid[0] + ( (ab_mid_bc_mid[0] / ab_mid_bc_mid_len) * ratio * ab_len ) ) , Math.floor(ab_mid[1] + ( ( ab_mid_bc_mid[1] / ab_mid_bc_mid_len ) * ratio * ab_len ) )];
			var mid_mid_b = [ this.points[i][0] - mid_mid[0], this.points[i][1] - mid_mid[1]  ];
		
			this.controlpoints.push( [ab_mid[0] + mid_mid_b[0], ab_mid[1] + mid_mid_b[1]] );
			this.controlpoints.push( [bc_mid[0] + mid_mid_b[0], bc_mid[1] + mid_mid_b[1]] );
		}
		this.controlpoints.push( this.points[this.points.length - 1] );
		
		var vectorFromLastPoint = [ this.controlpoints[this.controlpoints.length-2][0] - this.points[this.points.length-1][0], this.controlpoints[this.controlpoints.length-2][1] - this.points[this.points.length-1][1] ];
		var vectorFromLastPointLen = Math.sqrt(vectorFromLastPoint[0] * vectorFromLastPoint[0] +  vectorFromLastPoint[1] * vectorFromLastPoint[1]);
		var vectorFromLastPointLength20 = [ vectorFromLastPoint[0] / vectorFromLastPointLen * 20, vectorFromLastPoint[1] / vectorFromLastPointLen * 20 ];
		
		var orthogonalVectorX = null;
		
		if ( vectorFromLastPointLength20[0] != 0)
		{
			orthogonalVectorX = - (vectorFromLastPointLength20[1] * vectorFromLastPointLength20[1]) / vectorFromLastPointLength20[0];
		}
		else
		{
			//console.log('orthoY = ' + vectorFromLastPointLength20[1]);
			orthogonalVectorX = 1000;
		}
		var orthogonalVector = [ orthogonalVectorX, vectorFromLastPointLength20[1] ];
		
		var orthogonalVectorLen = Math.sqrt(orthogonalVector[0]*orthogonalVector[0] +  orthogonalVector[1]*orthogonalVector[1]);
		var orthogonalVectorLength10 = [ orthogonalVector[0] / orthogonalVectorLen * 10, orthogonalVector[1] / orthogonalVectorLen * 10 ];
		
		this.triangleCoords = [ 
		                       		[
		                       		 	this.points[this.points.length - 1][0] - vectorFromLastPointLength20[0] * 0.3, 
		                       		 	this.points[this.points.length - 1][1] - vectorFromLastPointLength20[1] * 0.3
		                       		],
		                       		[
		                       		 	vectorFromLastPointLength20[0]*0.7 + orthogonalVectorLength10[0] + this.points[this.points.length - 1][0],  
		                       		 	vectorFromLastPointLength20[1]*0.7 + orthogonalVectorLength10[1] + this.points[this.points.length - 1][1]
		                       		],
		                       		[
		                       		 	vectorFromLastPointLength20[0]*0.7 - orthogonalVectorLength10[0] + this.points[this.points.length - 1][0],  
		                       		 	vectorFromLastPointLength20[1]*0.7 - orthogonalVectorLength10[1] + this.points[this.points.length - 1][1]
		                       		]
		                      ];
		*/
	}
	else
	{
		this.points = Array();
	}
	this.color = color;
	this.shadowColor = shadowColor;
	this.lineWidth = lineWidth;
	
	this.mappedTriangle = new Polygon(null, 0, 0, 'black', 'red' );
	
	this.buildMappedControlPoints = function ( )
	{
		this.mappedControlpoints = new Array();
		// first controlpoint equals first anchor point
		this.mappedControlpoints.push( [ this.layer.renderer.wrapCoordinatesX ( this.points[0][0] ), this.points[0][1] ] );
		for (var i = 1; i < this.points.length - 1; i++)
		{
			// see http://www.antigrain.com/research/bezier_interpolation/index.html
			
			// relative vector a -> b
			var ab = [ this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ) - this.layer.renderer.wrapCoordinatesX ( this.points[i-1][0] ), this.points[i][1] - this.points[i-1][1] ];
			// absolute vecor to middle of a -> b
			var ab_mid = [ this.layer.renderer.wrapCoordinatesX ( this.points[i-1][0] ) + Math.floor(ab[0] / 2), this.points[i-1][1] + Math.floor(ab[1] / 2) ];
			// length of a -> b
			var ab_len = Math.sqrt(ab[0]*ab[0] + ab[1]*ab[1]);
			// relative vector b -> c
			var bc = [ this.layer.renderer.wrapCoordinatesX ( this.points[i+1][0] ) - this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ), this.points[i+1][1] - this.points[i][1] ];
			// absolute vector to middle of b -> c
			var bc_mid = [ this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ) + Math.floor(bc[0] / 2), this.points[i][1] + Math.floor(bc[1] / 2) ];
			// length of b -> c
			var bc_len = Math.sqrt( bc[0]*bc[0] + bc[1]*bc[1] );
			// relative vector from moddle of a -> b to middle of b -> c ( middles vector )
			var ab_mid_bc_mid = [ bc_mid[0] - ab_mid[0], bc_mid[1] - ab_mid[1] ];
			// length of middles vector
			var ab_mid_bc_mid_len = Math.sqrt( ab_mid_bc_mid[0]*ab_mid_bc_mid[0] + ab_mid_bc_mid[1]*ab_mid_bc_mid[1] );
			// reation between middles vector and a->b + b->c lengthes
			var ratio =  ab_mid_bc_mid_len / (ab_len + bc_len);
			
			// absolute vector to middle of middles vector
			var mid_mid = [ Math.floor(ab_mid[0] + ( (ab_mid_bc_mid[0] / ab_mid_bc_mid_len) * ratio * ab_len ) ) , Math.floor(ab_mid[1] + ( ( ab_mid_bc_mid[1] / ab_mid_bc_mid_len ) * ratio * ab_len ) )];
			// relative vector from middle of middles vector to b
			var mid_mid_b = [ this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ) - mid_mid[0], this.points[i][1] - mid_mid[1]  ];
		
			this.mappedControlpoints.push( [ab_mid[0] + mid_mid_b[0], ab_mid[1] + mid_mid_b[1]] );
			this.mappedControlpoints.push( [bc_mid[0] + mid_mid_b[0], bc_mid[1] + mid_mid_b[1]] );
		}
		// last controlpoint equals last anchor point
		this.mappedControlpoints.push( [ this.layer.renderer.wrapCoordinatesX ( this.points[this.points.length - 1][0] ), this.points[this.points.length - 1][1] ] );
		
		// build a triangle pointing in the direction of the last vector to use as a arrow head
		
		// vector from last anchor point to second last control point
		var vectorFromLastPoint = [ this.mappedControlpoints[ this.mappedControlpoints.length-2 ][0] - this.layer.renderer.wrapCoordinatesX ( this.points[ this.points.length-1 ][0] ), this.mappedControlpoints[ this.mappedControlpoints.length-2 ][1] - this.points[ this.points.length-1 ][1] ];
		// length of that vector
		var vectorFromLastPointLen = Math.sqrt(vectorFromLastPoint[0] * vectorFromLastPoint[0] +  vectorFromLastPoint[1] * vectorFromLastPoint[1]);
		// the vector rescaled to a length of 20
		var vectorFromLastPointLength20 = [ vectorFromLastPoint[0] / vectorFromLastPointLen * 20, vectorFromLastPoint[1] / vectorFromLastPointLen * 20 ];
		
		// get x coordinate for an orthgonal to our vector
		var orthogonalVectorX = null;
		
		// prevent divide by zero
		if ( vectorFromLastPointLength20[0] != 0)
		{
			// calculate x coordinate
			orthogonalVectorX = - (vectorFromLastPointLength20[1] * vectorFromLastPointLength20[1]) / vectorFromLastPointLength20[0];
		}
		else
		{
			// the x coordinate would stroive against infinity if fromLastVector x would have been 0 so just use a big number instead of infinity.
			orthogonalVectorX = 1000;
		}
		var orthogonalVector = [ orthogonalVectorX, vectorFromLastPointLength20[1] ];
		
		var orthogonalVectorLen = Math.sqrt(orthogonalVector[0]*orthogonalVector[0] +  orthogonalVector[1]*orthogonalVector[1]);
		var orthogonalVectorLength10 = [ orthogonalVector[0] / orthogonalVectorLen * 10, orthogonalVector[1] / orthogonalVectorLen * 10 ];
		
		this.mappedTriangleCoords = [ 
		                       		[
		                       		 	this.layer.renderer.wrapCoordinatesX ( this.points[this.points.length - 1][0] ) - vectorFromLastPointLength20[0] * 0.3, 
		                       		 	this.points[this.points.length - 1][1] - vectorFromLastPointLength20[1] * 0.3
		                       		],
		                       		[
		                       		 	vectorFromLastPointLength20[0]*0.7 + orthogonalVectorLength10[0] + this.points[this.points.length - 1][0],  
		                       		 	vectorFromLastPointLength20[1]*0.7 + orthogonalVectorLength10[1] + this.points[this.points.length - 1][1]
		                       		],
		                       		[
		                       		 	vectorFromLastPointLength20[0]*0.7 - orthogonalVectorLength10[0] + this.points[this.points.length - 1][0],  
		                       		 	vectorFromLastPointLength20[1]*0.7 - orthogonalVectorLength10[1] + this.points[this.points.length - 1][1]
		                       		]
		                      ];
		this.mappedTriangle.setVertices( this.mappedTriangleCoords.slice() );
	};
	
	this.setLayer = function( layer )
	{
		this.layer = layer;
		this.mappedTriangle.setLayer( layer );
	};

	this.render = function( context )
	{
		context.save();
		try
		{
			context.strokeStyle = color;
			context.lineWidth = this.lineWidth;
			context.shadowColor = shadowColor;
			context.shadowOffsetX = 1;
			context.shadowOffsetY = 1;
			context.shadowBlur = 2;

			if (this.points.length > 1)
			{
				this.buildMappedControlPoints( );
				context.beginPath();
				
				/*if ( this.boundingBox.upperLeft[0] != this.layer.renderer.wrapCoordinatesX( this.boundingBox.upperLeft[0] ) 
						|| this.boundingBox.lowerRight[0] != this.layer.renderer.wrapCoordinatesX( this.boundingBox.lowerRight[0] ) )
				{*/
					context.moveTo( this.layer.renderer.wrapCoordinatesX ( this.points[0][0] ), this.points[0][1] );
					
					for (var i = 1; i < this.points.length; i++)
					{
						// only draw if the wrapped points lie reasonable close togerther
						if ( Math.abs( this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ) - this.layer.renderer.wrapCoordinatesX ( this.points[i-1][0] ) ) < ( this.layer.renderer.context.canvas.width / 2 ) )
						{
							context.bezierCurveTo( this.mappedControlpoints[i*2-2][0], this.mappedControlpoints[i*2-2][1], 
												   this.mappedControlpoints[i*2-1][0], this.mappedControlpoints[i*2-1][1], 
												   this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ), this.points[i][1]);
						}
						// else skip this part of the bazier path
						else
						{
							context.moveTo( this.layer.renderer.wrapCoordinatesX ( this.points[i][0] ), this.points[i][1] );
						}
					}
					context.stroke( );
					context.beginPath( );
					context.strokeStyle = 'black';
					context.fillStyle = 'red';
					context.lineWidth = 1.0;
					/*var bounds = { upperLeft : [this.mappedTriangleCoords[0][0], this.mappedTriangleCoords[0][1]], lowerRight:[-Infinity,-Infinity]};
					if ( this.layer.renderer.isRectangleVisible(  ) )
					context.moveTo ( this.mappedTriangleCoords[0][0], this.mappedTriangleCoords[0][1] );
					context.lineTo ( this.mappedTriangleCoords[1][0], this.mappedTriangleCoords[1][1] );
					context.lineTo ( this.mappedTriangleCoords[2][0], this.mappedTriangleCoords[2][1] );
					context.closePath( );*/
					this.mappedTriangle.bla = true;
					this.mappedTriangle.render( context );
					//context.lineTo ( this.triangleCoords[0][0], this.triangleCoords[0][1] )
					context.fill( );
				}
				/*else
				{
					context.moveTo( this.points[0][0], this.points[0][1] );
					
					for (var i = 1; i < this.points.length; i++)
					{
						//console.log('BERZIER ' + i);
						
						context.bezierCurveTo( this.controlpoints[i*2-2][0], this.controlpoints[i*2-2][1], 
											   this.controlpoints[i*2-1][0], this.controlpoints[i*2-1][1], 
											   this.points[i][0], this.points[i][1] );
					}
					context.stroke( );
					/*context.beginPath( );
					context.strokeStyle = 'black';
					context.fillStyle = 'red';
					context.lineWidth = 1.0;
					context.moveTo ( this.triangleCoords[0][0], this.triangleCoords[0][1] );
					context.lineTo ( this.triangleCoords[1][0], this.triangleCoords[1][1] );
					context.lineTo ( this.triangleCoords[2][0], this.triangleCoords[2][1] );
					context.closePath( );
					//context.lineTo ( this.triangleCoords[0][0], this.triangleCoords[0][1] )
					context.fill( );*/
				//}
			//}
		

			/*context.beginPath();
			context.fillStyle = 'red';
			for (var i = 0; i < this.controlpoints.length; i++ )
			{
				context.arc ( this.controlpoints[i][0], this.controlpoints[i][1], 5, 0, 6.283185307179586, false);
			}
			context.fill();
			
			context.beginPath();
			context.fillStyle = 'blue';
			for (var i = 0; i < this.points.length; i++ )
			{
				context.arc ( this.points[i][0], this.points[i][1], 3, 0, 6.283185307179586, false);
			}
			context.fill();*/
		} 
		finally 
		{
			context.restore();
		}
	};
};

var Polygon = function( vertices, x, y, strokeStyle, fillStyle, lineWidth )
{
	this.boundingBox = { upperLeft : [ Infinity, Infinity ], lowerRight : [ 0, 0 ] };
	this.setVertices = function( vertices )
	{
		this.vertices = vertices;
		
		if ( vertices != null )
		{
			for ( var i = 0; i < vertices.length; i++ )
			{
				if ( vertices[i][0] < this.boundingBox.upperLeft[0] )
				{
					this.boundingBox.upperLeft[0] = vertices[i][0];
				}
				if ( vertices[i][1] < this.boundingBox.upperLeft[1] )
				{
					this.boundingBox.upperLeft[1] = vertices[i][1];
				}
				if ( vertices[i][0] > this.boundingBox.lowerRight[0] )
				{
					this.boundingBox.lowerRight[0] = vertices[i][0];
				}
				if ( vertices[i][1] > this.boundingBox.lowerRight[1] )
				{
					this.boundingBox.lowerRight[1] = vertices[i][1];
				}
			}
		}
	};
	
	this.super = Drawable;
	this.super(x, y);
	this.setVertices ( vertices );
	
	this.strokeStyle = strokeStyle;
	this.fillStyle = fillStyle;
	if (lineWidth != null)
	{ 
		this.lineWidth = lineWidth;
	}
	else
	{
		this.lineWidth = 1.0;
	}

	this.render = function( context )
	{
		if ( this.vertices != null && this.vertices.length > 0 && this.layer.renderer.isRectangleVisible(this.boundingBox) )
		{
			context.save();
			context.fillStyle   = this.fillStyle;
			context.strokeStyle = this.strokeStyle;
			context.lineWidth   = this.lineWidth;
			context.beginPath();

			context.moveTo( this.layer.renderer.wrapCoordinatesX ( this.vertices[0][0] ), this.vertices[0][1] );
			for ( var i = 1; i < this.vertices.length; i++ )
			{
				context.lineTo( this.layer.renderer.wrapCoordinatesX ( this.vertices[i][0] ) , this.vertices[i][1] );
			}
			
			context.closePath();
			context.fill( );
			context.stroke( );
			context.restore( );
		};
	};
};

function Layer(renderer, index)
{
	this.renderer = renderer;
	this.currentImageData = null;
	this.bufferImageData = false;
	this.needsRepaint = true;
	this.drawablesCount = 0;
	this.index = index;
	
	this.render = function ( context ) 
	{
		for ( var drawable in this.drawables )
		{
			this.drawables[drawable].render( context );
		}
		this.needsRepaint = false;
		return ( this.drawablesCount > 0 );
	};
	
	this.invalidate = function ( )
	{
		this.needsRepaint = true;
		this.renderer.invalidate( );
	};
	
	this.addDrawable = function ( drawable, name )
	{
		this.removeDrawable( name );
		
		this.drawables[name] = drawable;
		drawable.setLayer( this );
		this.drawablesCount++;
		this.invalidate();
	};
	
	this.removeDrawable = function ( name )
	{
		if ( name in this.drawables )
		{
			this.drawables[name].setLayer( null );
			this.drawables[name] = null;
			delete this.drawables[name];
			this.drawablesCount--;
			this.invalidate();
		}
	};
	
	this.clearDrawables = function ( )
	{
		this.drawables = { };
		this.drawablesCount = 0;
		this.invalidate();
	};
	
	this.drawables = { };
}

var Renderer = function ( context, width, height )
{
	this.context = context;
	this.layers = Array();
	this.intervalHandle = null;
	this.viewPortOffset = [ 0, 0 ];
	this.viewPortZoom = 1.0;
	this.needsRepaint = true;
	this.isBufferungSuspended = false;
	this.width = width;
	this.height = height;
	
	this.loopInterval = 50;
	
	// TODO: Replace those for direct calls after profiling is done
	this.putImageData = function(context, backgroundImage){context.putImageData( backgroundImage, 0, 0 );};
	this.getImageData = function(context){return context.getImageData( 0, 0, context.canvas.width, context.canvas.height );};	
	
	this.newLayer = function( )
	{
		if ( this.layers.length > 0 )
		{
			this.layers[this.layers.length-1].bufferImageData = true;
		}
		// add a new Layer
		this.layers.push(new Layer(this, this.layers.length));
	};
	
	this.deleteLayer = function( )
	{
		// remove the last layer
		this.layers.pop();
		if ( this.layers.length > 0 )
		{
			this.layers[this.layers.length-1].bufferImageData = false;
		}
	};
	
	this.render = function ( )
	{
		if ( this.needsRepaint )
		{
			var repaint = false;
			var backgroundBuffer = null;
			this.context.save();
			this.context.translate( -this.viewPortOffset[0], -this.viewPortOffset[1] );
			this.context.scale( this.viewPortZoom, this.viewPortZoom );
			for( var i = 0; i < this.layers.length; i++ )
			{
				if ( ! repaint && this.layers[i].needsRepaint )
				{
					//console.log ('found repaint request on layer ' + i);
					if ( backgroundBuffer != null )
					{
						//console.log('putting image data on layer ' + i);
						this.putImageData( context, backgroundBuffer );
					}
					else
					{
						//console.log('clearing rect, because no background buffer is available on layer ' + i);
						this.context.clearRect( this.viewPortOffset[0] / this.viewPortZoom, this.viewPortOffset[1] / this.viewPortZoom, this.context.canvas.width / this.viewPortZoom, this.context.canvas.width  / this.viewPortZoom );
					}
				}
				if ( repaint = ( this.layers[i].needsRepaint || repaint ) )
				{
					//console.log('rendering image on layer ' + i);
					// if layer rendered something and it was not the last layer
					if ( this.layers[i].render( this.context ) && ( i < (this.layers.length - 1) ) )
					{						
						// save the new data if buffering isn't suspended
						if ( ! this.isBufferungSuspended )
						{
							//console.log('getting image data on layer ' + i);
							this.layers[i].currentImageData = this.getImageData( this.context );
						}
					}
					else
					{					
						this.layers[i].currentImageData = backgroundBuffer;
					}
				}
				backgroundBuffer = this.layers[i].currentImageData;
			}
			this.context.restore( );
			this.needsRepaint = false;
		}
	};
	
	this.setViewportZoom = function( zoom )
	{
		this.viewPortOffset[0] = parseInt ( Math.floor( this.viewPortOffset[0] * ( zoom / this.viewPortZoom ) ) );// * this.viewPortZoom;		
		this.viewPortOffset[1] = parseInt ( Math.floor( this.viewPortOffset[1] * ( zoom / this.viewPortZoom ) ) );
		
		this.wrapDistance = ( this.width - ( this.context.canvas.width / this.viewPortZoom ) ) / 2;

		this.viewPortZoom = zoom;
		
		this.invalidateAll( );
	};
	
	this.setViewportOffset = function( vector )
	{
		if ( ( vector[0] + parseInt( this.context.canvas.width ) )  > ( this.width * 1.5 * this.viewPortZoom ) )
		{
			this.viewPortOffset[0] = vector[0] - ( this.width * this.viewPortZoom );
		}
		else if ( vector[0] < ( - this.width / 2 * this.viewPortZoom ) )
		{
			this.viewPortOffset[0] = vector[0] + ( this.width * this.viewPortZoom );
		}
		else
		{
			this.viewPortOffset[0] = vector[0];
		}
		
		if ( vector[1] < 0)
		{
			this.viewPortOffset[1] = 0;
		} 
		else if ( vector[1] > ( this.height * this.viewPortZoom ) - ( this.context.canvas.height ) )
		{
			this.viewPortOffset[1] = ( this.height * this.viewPortZoom ) - this.context.canvas.height;
		}
		else
		{
			this.viewPortOffset[1] = vector[1];
		}
		 //console.log(vector[1]);
		this.invalidateAll( );
	};
	
	this.getWorldCoordinates = function ( x, y )
	{
		return [ this.unwrapCoordinatesX ( ( this.context.canvas.offsetLeft + x + this.viewPortOffset[0] ) / this.viewPortZoom ) , ( this.context.canvas.offsetTop + y + this.viewPortOffset[1] ) / this.viewPortZoom ];
	};                                                                                                                                                                      
	
	this.unwrapCoordinatesX = function ( x )
	{
		if ( x < 0 )
		{
			return x + this.width;
		}
		if ( x > this.width )
		{
			return x - this.width;
		}
		else
		{
			return x;
		}
	};
	
	this.wrapCoordinatesX = function ( x )
	{
		//console.log('distance ' + wrapDistance);
		if ( ( x + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) )
		{
			return x + this.width;
		}
		else if ( ( x - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom )
		{
			//console.log('wrapping right');
			return x - this.width;
		}
		else
		{
			//console.log('did not wrap');
			return x;
		}
	};
	
	this.invalidateAll = function( )
	{
		if ( this.layers.length > 0 )
		{
			this.layers[0].invalidate( );
		}
	};
	
	this.isRectangleVisible = function( rect )
	{
		return this.wrapCoordinatesX ( rect.upperLeft[0] ) > this.viewPortOffset[0] / this.viewPortZoom && this.wrapCoordinatesX ( rect.upperLeft[0] ) < ( this.viewPortOffset[0]  + parseInt(this.context.canvas.width) ) / this.viewPortZoom
			|| this.wrapCoordinatesX ( rect.lowerRight[0] ) > this.viewPortOffset[0] / this.viewPortZoom && this.wrapCoordinatesX ( rect.lowerRight[0] ) < ( this.viewPortOffset[0] + parseInt(this.context.canvas.width) ) / this.viewPortZoom;
//		       && rect.upperLeft[1] > this.viewPortOffset[1] && rect.lowerRight[1] < this.viewPortOffset[1] + parseInt(this.context.canvas.height);
	};
	
	this.invalidate = function( )
	{
		this.needsRepaint = true;
	};
	
	this.suspendBuffering = function( )
	{
		this.isBufferungSuspended = true;
	};
	
	this.resumeBuffering = function( )
	{
		this.isBufferungSuspended = false;
		// force repainting of layer stack to create buffers
		this.invalidateAll( );
	};
	
	this.startRendering = function( )
	{
		var renderer = this;
		this.intervalHandle = setInterval(function (){
					renderer.render( );
				}, this.loopInterval);
	};
	
	this.suspendRendering = function( )
	{
		clearInterval(this.intervalHandle);
	};
	
	this.setViewportZoom(1.0);
};