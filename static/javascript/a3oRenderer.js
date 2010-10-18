/** Baseclass for all elements a Renderer can draw.
 * 
 * Drawables are held within layers of the Renderer class.
 * Each Drawable represents a specific element such as 
 * an image or a polygon. One level of inheritence is
 * supported by calling the Drawable function from within
 * a descendant class.
 * 
 * @param x position of the drawable 
 * @param y position of the drawable.
 * @return Drawable object
 */
var Drawable = function( x, y )
{
	/** The drawable'S position.
	 * 
	 */
	this.position = [x, y];
	
	/** The drawable's parent layer.
	 * 
	 * Do write this property directly. Use setLayer instead.
	 */
	this.layer = null;
	
	/** Draws the Drawable.
	 * 
	 * Since this is an 'abstract' class this method does 
	 * nothing and must be overritten by an descendant.
	 */
	this.render = function( context ) 
	{ 
	};
	
	/** Sets the parent layer.
	 * 
	 * The parent layer is the layer the Drawable is held in.
	 * It is responsible for magaing and calling the Drawables
	 * render method.
	 */
	this.setLayer = function ( layer )
	{
		this.layer = layer;
	};
	
	/** Invalidates this drawable and thus it's parent layer.
	 * 
	 * When this function is called the drawable will call it's
	 * parent layer invalidate() method which in turn will cause
	 * the parent layer to be redrawn upon the next rendering cycle.
	 */
	this.invalidate = function( )
	{
		this.layer.invalidate();
	};
};

/** Sprites draw images for a Renderer
 * 
 * Sprites inherit from Drawables and define a static
 * image that can be drawn to a Renderer.
 * It uses it's renderer's wrapCoordinatesX to position
 * it correctly on a wrapped (that is torus-like) drawing
 * surface.
 * 
 * @param image The image dawn.
 * @param x position of the image.
 * @param y position of the image.
 * @return Sprite object.
 */
var Sprite = function( image, x, y )
{
	this.super = Drawable;
	this.super(x, y);
	this.image = image;
	this.render = function( context )
	{
		var renderer = this.layer.renderer;
		if ( renderer.isRectangleVisible( { upperLeft : this.position, lowerRight: [ this.image.width + this.position[0], this.image.height + this.position[1] ] } ) )
		{
			var wrappedX = this.position[0];
			wrappedX = ( ( wrappedX + renderer.wrapDistance ) < ( renderer.viewPortOffset[0] / renderer.viewPortZoom ) )  ? wrappedX + renderer.width : ( ( wrappedX - renderer.wrapDistance ) > ( renderer.viewPortOffset[0] + context.canvas.width ) / renderer.viewPortZoom ) ? wrappedX - renderer.width : wrappedX;
			context.drawImage( this.image, /*this.layer.renderer.wrapCoordinatesX(*/ wrappedX , this.position[1] );
		}
	};
};

/** Pathes draw connected bezier curves.
 * 
 *  A path will draw a connected, multipart bezier curve over a
 *  set of given anchor points. It will calculate it's own control
 *  points to create a smooth, connected, multipart bezier curve.
 *  
 *  At the tip of the curve a pointy arrow head is drawn that shows 
 *  in the direction the curve was heading just before terminating.
 * 
 * @param points array of (anchor-) vertices
 * @param color color of the line
 * @param lineWidth width of the line
 * @param shadowColor color of the shadow dropped by the line
 * @return Path object.
 */
var Path = function ( points, color, lineWidth, shadowColor )
{
	// we can only draw a bezier curve if we have at least 2 points
	if (points && (points.length > 1))
	{
		this.points = points;
		
		// setup the anchor points bounding box. Note that this is
		// is not the curves bounding box and the curve may penetrate 
		// this box due to controlpoints.
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
		// unmapped control points - those are not needed atm, so removed by comment
		
		/*this.controlpoints = new Array();
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
		                      ];*/
	}
	else
	{
		// if there are not enough points, init an empty array
		this.points = [];
	}
	this.color = color;
	this.shadowColor = shadowColor;
	this.lineWidth = lineWidth;
	
	// this polygon will hold our triangle we use to paint the arrow head
	this.mappedTriangle = new Polygon(null, 0, 0, 'black', 'red' );
	
	
	/** Creates wrapped control points
	 * 
	 * this method create control points by wrap-mapping each anchor point and
	 * calculating the control points in mapped coordinate space using this method:
	 * 
	 * http://www.antigrain.com/research/bezier_interpolation/index.html
	 */
	this.buildMappedControlPoints = function ( )
	{
		this.mappedControlpoints = [];
		
		var wrapXLeft = ( renderer.viewPortOffset[0] / renderer.viewPortZoom ) - renderer.wrapDistance;
		var wrapXRight = ( renderer.viewPortOffset[0] + renderer.context.canvas.width ) / renderer.viewPortZoom + renderer.wrapDistance;
		var wrappedX = this.points[0][0];
		wrappedX = ( wrappedX < wrapXLeft ) ? wrappedX + renderer.width : ( wrappedX > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
		
		// first controlpoint equals first anchor point
		this.mappedControlpoints.push( [ wrappedX, this.points[0][1] ] );
		for (var i = 1; i < this.points.length - 1; i++)
		{
			// see http://www.antigrain.com/research/bezier_interpolation/index.html
			
			var a = this.points[i-1].slice();
			a[0] = ( a[0] < wrapXLeft ) ? a[0] + renderer.width : ( a[0] > wrapXRight ) ? a[0] - renderer.width : a[0];
			var b = this.points[i].slice();
			b[0] = ( b[0] < wrapXLeft ) ? b[0] + renderer.width : ( b[0] > wrapXRight ) ? b[0] - renderer.width : b[0];
			var c = this.points[i+1].slice();
			c[0] = ( c[0] < wrapXLeft ) ? c[0] + renderer.width : ( c[0] > wrapXRight ) ? c[0] - renderer.width : c[0];
			
			// relative vector a -> b
			var ab = [ b[0] - a[0], b[1] - a[1] ];
			// absolute vecor to middle of a -> b
			var ab_mid = [ a[0] + Math.floor(ab[0] / 2), a[1] + Math.floor(ab[1] / 2) ];
			// length of a -> b
			var ab_len = Math.sqrt(ab[0]*ab[0] + ab[1]*ab[1]);
			// relative vector b -> c
			var bc = [ c[0] - b[0], c[1] - b[1] ];
			// absolute vector to middle of b -> c
			var bc_mid = [ b[0] + Math.floor(bc[0] / 2), b[1] + Math.floor(bc[1] / 2) ];
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
			var mid_mid_b = [ b[0] - mid_mid[0], b[1] - mid_mid[1]  ];
		
			this.mappedControlpoints.push( [ab_mid[0] + mid_mid_b[0], ab_mid[1] + mid_mid_b[1]] );
			this.mappedControlpoints.push( [bc_mid[0] + mid_mid_b[0], bc_mid[1] + mid_mid_b[1]] );
		}
		wrappedX = this.points[this.points.length - 1][0];
		wrappedX = ( wrappedX < wrapXLeft ) ? wrappedX + renderer.width : ( wrappedX > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
		// last controlpoint equals last anchor point
		this.mappedControlpoints.push( [ wrappedX, this.points[this.points.length - 1][1] ] );
		
		// build a triangle pointing in the direction of the last vector to use as a arrow head
		
		// vector from last anchor point to second last control point
		var vectorFromLastPoint = [ this.mappedControlpoints[ this.mappedControlpoints.length-2 ][0] - wrappedX, this.mappedControlpoints[ this.mappedControlpoints.length-2 ][1] - this.points[ this.points.length-1 ][1] ];
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
			// the x coordinate would strive against infinity if fromLastVector x would have been 0 so just use a big number instead of infinity.
			// there actually is a fricking point in the big world map where this issue arises.
			orthogonalVectorX = 10000;
		}
		var orthogonalVector = [ orthogonalVectorX, vectorFromLastPointLength20[1] ];
		
		// length of orthognal vector
		var orthogonalVectorLen = Math.sqrt(orthogonalVector[0]*orthogonalVector[0] +  orthogonalVector[1]*orthogonalVector[1]);
		// orthogonal vector scaled to 10 units / pixels length
		var orthogonalVectorLength10 = [ orthogonalVector[0] / orthogonalVectorLen * 10, orthogonalVector[1] / orthogonalVectorLen * 10 ];
		
		// assign the triangle coordinates to the mappedTriangle polygon
		this.mappedTriangle.setVertices( [ 
				                       		[
				                       		 	wrappedX - vectorFromLastPointLength20[0] * 0.3, 
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
				                       	 ] );
	};
	
	/** Overwritten. Sets the Path's parent layer.
	 * 
	 *  Sets the Path's parent layer. Also updates the mappedTrianle parent layer.
	 */
	this.setLayer = function( layer )
	{
		this.layer = layer;
		this.mappedTriangle.setLayer( layer );
	};

	/** Renders the path to the drawing surface.
	 * 
	 * Uses wrapCoordinatesX to map the path in wrapped coordinate space.
	 * Also ensures that no "crosspathing" arises by not connecting anchor points
	 * that are wrapped to different sides of the map.
	 * Because this is done by checking if the anchor points are extraordenary far 
	 * apart it might actually arise issues on some freaky maps with very distant
	 * path points. Normally this should do the trick though.
	 * 
	 * @param context The HTML5 2d canvas context to draw to.
	 * @return true if this method actually drew something, else false
	 */
	this.render = function( context )
	{
		if (this.points.length > 1)
		{
			context.save();

			context.strokeStyle = color;
			context.lineWidth = this.lineWidth;
			context.shadowColor = shadowColor;
			context.shadowOffsetX = 1;
			context.shadowOffsetY = 1;
			context.shadowBlur = 2;
		
			// build mapped control points, although it seems pretty intense building this
			// each time the path is rendered a rebuild is actually neccessary most of the 
			// time and it takes not close as much time as you might fear. (~ avg 0.2ms on my machine)
			this.buildMappedControlPoints( );
			
			var wrapXLeft = ( renderer.viewPortOffset[0] / renderer.viewPortZoom ) - renderer.wrapDistance;
			var wrapXRight = ( renderer.viewPortOffset[0] + renderer.context.canvas.width ) / renderer.viewPortZoom + renderer.wrapDistance;
			var wrappedX = this.points[0][0];
			wrappedX = ( wrappedX < wrapXLeft ) ? wrappedX + renderer.width : ( wrappedX > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
			
			var wrappedX2;
			
			context.beginPath();				
			context.moveTo( wrappedX , this.points[0][1] );
			
			for (var i = 1; i < this.points.length; i++)
			{
				wrappedX = this.points[i][0];
				wrappedX = ( wrappedX < wrapXLeft ) ? wrappedX + renderer.width : ( wrappedX > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
				wrappedX2 = this.points[i-1][0];
				wrappedX2 = ( wrappedX2 < wrapXLeft ) ? wrappedX2 + renderer.width : ( wrappedX2 > wrapXRight ) ? wrappedX2 - renderer.width : wrappedX2;
									
				// only draw if the wrapped points lie reasonable close together
				if ( Math.abs( wrappedX - wrappedX2 ) < ( this.layer.renderer.context.canvas.width / 2 ) )
				{
					context.bezierCurveTo( this.mappedControlpoints[i*2-2][0], this.mappedControlpoints[i*2-2][1], 
										   this.mappedControlpoints[i*2-1][0], this.mappedControlpoints[i*2-1][1], 
										   wrappedX , this.points[i][1] );
				}
				// else skip this part of the bezier path to prevent 'crosspathing', that is a path crossing the entire
				// world map because one end pf the bezier was mapped to the left side and the other to the right
				// side of the world map
				else
				{
					context.moveTo( wrappedX , this.points[i][1] );
				}
			}
			context.stroke( );

			this.mappedTriangle.render( context );
			
			context.restore();
				
			return true;
		}
		return false;
	};
};

var Text = function ( string, font, x, y, strokeStyle, fillStyle, align, maxWidth )
{
	this.super = Drawable;
	this.super( x,y );
	this.string = string;
	this.font = font;
	if ( align != null )
	{
		this.align = align;
	}
	else
	{
		this.align = 'start';
	}
	this.maxWidth = maxWidth;
	this.strokeStyle = strokeStyle;
	this.fillStyle = fillStyle;
	
	this.getTextWdith = function( context )
	{
		context.save( );
		
		context.font = this.font;
		context.textAlign = this.align;

		var result = context.measureText( this.string ).width;
		context.restore( );
		return result;
	};
	
	this.render = function( context )
	{
		context.save( );
		
		context.font = this.font;
		context.textAlign = this.align;
		context.strokeStyle = this.strokeStyle;
		context.fillStyle = this.fillStyle;
		
		context.fillText( this.string, this.position[0], this.position[1] );
		context.strokeText( this.string, this.position[0], this.position[1] );
		
		context.restore( );
	};
	
	this.setText = function ( string )
	{
		this.string = string;
		this.invalidate( );
	};
};

var Polygon = function( vertices, x, y, strokeStyle, fillStyle, lineWidth )
{
	this.boundingBox = { upperLeft : [ Infinity, Infinity ], lowerRight : [ 0, 0 ] };
	this.setVertices = function( vertices )
	{
		this.vertices = vertices;
		this.boundingBox = { upperLeft : [ Infinity, Infinity ], lowerRight : [ -Infinity, -Infinity ] };
		
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
		var renderer = this.layer.renderer;
		if ( this.vertices && this.vertices.length && renderer.isRectangleVisible( this.boundingBox ) )
		{
			context.save();

			context.fillStyle   = this.fillStyle;
			context.strokeStyle = this.strokeStyle;
			context.lineWidth   = this.lineWidth;
			context.beginPath();

			var wrapXLeft = ( renderer.viewPortOffset[0] / renderer.viewPortZoom ) - renderer.wrapDistance;
			var wrapXRight = ( renderer.viewPortOffset[0] + renderer.context.canvas.width ) / renderer.viewPortZoom + renderer.wrapDistance;
			var wrappedX = this.vertices[0][0];
			wrappedX = ( wrappedX < wrapXLeft ) ? wrappedX + renderer.width : ( wrappedX > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
			
			context.moveTo( wrappedX , this.vertices[0][1] );
			
			var i = this.vertices.length;
			while ( --i ) 
			{
				wrappedX = this.vertices[i][0];
				wrappedX = ( wrappedX < wrapXLeft )  ? wrappedX + renderer.width : ( ( wrappedX ) > wrapXRight ) ? wrappedX - renderer.width : wrappedX;
				context.lineTo( wrappedX  , this.vertices[i][1] );
			};
			
			context.closePath();
			context.fill( );
			context.stroke( );
			
			context.restore( );
			
			return true;
		};
		return false;
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
		var rendered = false;
		for ( var drawable in this.drawables )
		{
			rendered = this.drawables[drawable].render( context ) || rendered;
		}
		this.needsRepaint = false;
		return rendered;
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
		this.invalidate( );
	};
	
	this.removeDrawable = function ( name )
	{
		if ( name in this.drawables )
		{
			this.drawables[name].setLayer( null );
			this.drawables[name] = null;
			delete this.drawables[name];
			this.drawablesCount--;
			this.invalidate( );
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
	
	this.loopInterval = 100;
	
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
			var layerCount;
			this.context.save();
			this.context.translate( -this.viewPortOffset[0], -this.viewPortOffset[1] );
			this.context.scale( this.viewPortZoom, this.viewPortZoom );
			layerCount = this.layers.length;
			for( var i = 0; i < layerCount; i++ )
			{
				if ( ! repaint && this.layers[i].needsRepaint )
				{
					if ( backgroundBuffer != null )
					{
						this.context.putImageData( backgroundBuffer, 0, 0 );
					}
					else
					{
						this.context.clearRect( this.viewPortOffset[0] / this.viewPortZoom, this.viewPortOffset[1] / this.viewPortZoom, this.context.canvas.width / this.viewPortZoom, this.context.canvas.width  / this.viewPortZoom );
					}
				}
				if ( repaint = ( this.layers[i].needsRepaint || repaint ) )
				{
					// if layer rendered something and it was not the last layer
					if ( this.layers[i].render( this.context ) && ( i < (this.layers.length - 1) ) )
					{						
						// save the new data if buffering isn't suspended
						if ( ! this.isBufferingSuspended )
						{
							this.layers[i].currentImageData = this.context.getImageData( 0, 0, this.context.canvas.width, this.context.canvas.height );
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
		if ( ( vector[0] + (+this.context.canvas.width ) )  > ( this.width * 1.5 * this.viewPortZoom ) )
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
		else if ( vector[1] > ( this.height * this.viewPortZoom ) - (+this.context.canvas.height ) )
		{
			this.viewPortOffset[1] = ( this.height * this.viewPortZoom ) - (+this.context.canvas.height);
		}
		else
		{
			this.viewPortOffset[1] = vector[1];
		}
		this.invalidateAll( );
	};
	
	this.getWorldCoordinates = function ( x, y )
	{
		//x = ( ( x + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) ) ? x + this.width : ( ( x - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom ) ? x - this.width : x;
		return [ this.unwrapCoordinatesX( ( x - this.context.canvas.offsetLeft + this.viewPortOffset[0] ) / this.viewPortZoom ), ( y - this.context.canvas.offsetTop + this.viewPortOffset[1] ) / this.viewPortZoom ];
	};                                                                                                                                                                      
	
	this.unwrapCoordinatesX = function ( x )
	{
		if ( x < 0 )
		{
			return x + this.width;
		}
		if ( x >= this.width )
		{
			return x - this.width;
		}
		else
		{
			return x;
		}
	};
	
	// This used to be a method but was "inlined" for performance reasons.
	// Unfortunately Javascript function calls are tremendously slow in some implementations (eg Firefox).
	// While panning the viewport this function was called up to 1 million times within a short timeframe
	// which brought Firefox' Javascript implementation to it's knees. While this worked fine in Chrome it
	// absolutely killed Firefox. 
	// Since there is no way to write macros or inline function in Javascript I moved the logic of the
	// function to each place it was called to save the actual function call. This turned out to be a
	// significant performance boost in Firefox.
	// I might fix this by using PHP as a macro replacement and do something like JS_wrapCoordinatesX( $x )
	// which will echo the appropriate Javascript logic to reduce code redundancy.
	//this.wrapCoordinatesX = function ( x )
	//{
		/*if ( ( x + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) ) 
		{
			return x + this.width;
		} 
		else if ( ( x - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom )
		{
			return x - this.width;
		} 
		else 
		{
			return x;
		}*/
		//return ( ( x + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) )  ? x + this.width : ( ( x - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom ) ? x - this.width : x;
	//};
	
	this.invalidateAll = function( )
	{
		if ( this.layers.length > 0 )
		{
			this.layers[0].invalidate( );
		}
	};

	this.isRectangleVisible = function( rect )
	{
		var wrappedUL = rect.upperLeft[0];
		wrappedUL = ( ( wrappedUL + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) )  ? wrappedUL + this.width : ( ( wrappedUL - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom ) ? wrappedUL - this.width : wrappedUL;
		var wrappedLR = rect.lowerRight[0];
		wrappedLR = ( ( wrappedLR + this.wrapDistance ) < ( this.viewPortOffset[0] / this.viewPortZoom ) )  ? wrappedLR + this.width : ( ( wrappedLR - this.wrapDistance ) > ( this.viewPortOffset[0] + this.context.canvas.width ) / this.viewPortZoom ) ? wrappedLR - this.width : wrappedLR;
		var canvasWidth = (+this.context.canvas.width);
		var canvasHeight = (+this.context.canvas.height);
		return ( wrappedUL > this.viewPortOffset[0] / this.viewPortZoom && wrappedUL < ( this.viewPortOffset[0]  + canvasWidth ) / this.viewPortZoom
			|| wrappedLR > this.viewPortOffset[0] / this.viewPortZoom && wrappedLR < ( this.viewPortOffset[0] + canvasWidth ) / this.viewPortZoom 
			|| wrappedUL <= this.viewPortOffset[0] / this.viewPortZoom && wrappedLR >= ( this.viewPortOffset[0] + canvasWidth ) / this.viewPortZoom )
			&& ( ( rect.upperLeft[1] > ( this.viewPortOffset[1] / this.viewPortZoom ) && rect.upperLeft[1] < ( ( this.viewPortOffset[1]) + canvasHeight ) / this.viewPortZoom )
			|| ( rect.lowerRight[1] > ( this.viewPortOffset[1] / this.viewPortZoom ) && rect.lowerRight[1] < ( ( this.viewPortOffset[1]) + canvasHeight ) / this.viewPortZoom ) 
			|| rect.upperLeft[1] <= ( this.viewPortOffset[1] / this.viewPortZoom ) && rect.lowerRight[1] >= ( ( this.viewPortOffset[1]) + canvasHeight ) / this.viewPortZoom ) ;
	};
	
	this.invalidate = function( )
	{
		this.needsRepaint = true;
	};
	
	this.suspendBuffering = function( )
	{
		this.isBufferingSuspended = true;
	};
	
	this.resumeBuffering = function( )
	{
		this.isBufferingSuspended = false;
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