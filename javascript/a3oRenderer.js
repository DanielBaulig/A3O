var Drawable = function( x, y )
{
	this.position = [x, y];
	this.layer = null;
	
	this.render = function( context ) 
	{ 
	};
	
	this.invalidate = function( )
	{
		this.layer.invalidate();
	};
};

var ShadowStyle = function ( color, offsetX, offsetY, blur)
{
	this.color = color;
	this.offsetX = offsetX;
	this.offsetY = offsetY;
	this.blur = blur;
	
	this.apply = function( context )
	{
		context.save();
		context.shadowColor = this.color;
		context.shadowOffsetX = this.offsetX;
		context.shadowOffsetY = this.offsetY;
		context.shadowBlur = this.blur;
	};
	
	this.unapply = function ( context )
	{
		context.restore();
	};
};

var Sprite = function( image, x, y )
{
	this.super = Drawable;
	this.super(x, y);
	this.image = image;
	this.render = function( context )
	{
		context.drawImage( this.image, this.position[0], this.position[1] );
	};
};

var Path = function ( points, color, lineWidth, shadowColor )
{
	if (points != null && (points.length > 0))
	{
		// see http://www.antigrain.com/research/bezier_interpolation/index.html
		this.points = points;
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
			/*console.log('ratio ' + ratio);
			console.log('ab_mid_bc_mid ' + ab_mid_bc_mid);
			console.log('ab ' + ab);
			console.log('bc ' + bc);
			console.log('ab_mid ' + ab_mid);
			console.log('ab_len' + ab_len);
			console.log('bc_mid '+ bc_mid);
			console.log('bc_len '+ bc_len);
			console.log('ab_mid_bc_mid '+ ab_mid_bc_mid);
			console.log('ab_mid_bc_mid_len '+ ab_mid_bc_mid_len);*/
			
			var mid_mid = [ Math.floor(ab_mid[0] + ( (ab_mid_bc_mid[0] / ab_mid_bc_mid_len) * ratio * ab_len ) ) , Math.floor(ab_mid[1] + ( ( ab_mid_bc_mid[1] / ab_mid_bc_mid_len ) * ratio * ab_len ) )];
			//console.log('mid_mid ', mid_mid);
			//var b_mid_mid = [ this.points[i][0] - mid_mid[0], this.points[i][1] - mid_mid[1]  ];
			var mid_mid_b = [ this.points[i][0] - mid_mid[0], this.points[i][1] - mid_mid[1]  ];
			//console.log('mid_mid_b ', mid_mid_b);
			//console.log('CONTROLPOINTS ' + [this.points[i-1][0] + ab_mid[0] + mid_mid_b[0], this.points[i-1][1] + ab_mid[1] + mid_mid_b[1]] );
			this.controlpoints.push( [ab_mid[0] + mid_mid_b[0], ab_mid[1] + mid_mid_b[1]] );
			this.controlpoints.push( [bc_mid[0] + mid_mid_b[0], bc_mid[1] + mid_mid_b[1]] );
			/*this.controlpoints.push( [ b_mid_mid[0] - ab_mid[0] + this.points[i][0], b_mid_mid[1] - ab_mid[1] + this.points[i][1]] );
			this.controlpoints.push( [ b_mid_mid[0] - bc_mid[0] + this.points[i][0], b_mid_mid[1] - bc_mid[1] + this.points[i][1] ] );*/
		}
		this.controlpoints.push( this.points[this.points.length - 1] );
	}
	else
	{
		this.points = Array();
	}
	this.color = color;
	this.shadowColor = shadowColor;
	this.lineWidth = lineWidth;
	
	this.render = function( context )
	{
		context.save();
		try
		{
			
			context.fillStyle = color;
			context.lineWidth = this.lineWidth;
			context.shadowColor = shadowColor;
			context.shadowOffsetX = 1;
			context.shadowOffsetY = 1;
			context.shadowBlur = 1;

			context.beginPath();

			if (this.points.length > 1)
			{
				context.moveTo( this.points[0][0], this.points[0][1] );
				
				for (var i = 1; i < this.points.length; i++)
				{
					//console.log('BERZIER ' + i);
					
					context.bezierCurveTo( this.controlpoints[i*2-2][0], this.controlpoints[i*2-2][1], 
										   this.controlpoints[i*2-1][0], this.controlpoints[i*2-1][1], 
										   this.points[i][0], this.points[i][1]);
				}
			}
		
			context.stroke( );
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

var Bezier = function ( points, lineWidth )
{
	this.super = Drawable;
	this.super(NaN, NaN);
	this.points = points;
	
	if ( lineWidth != null )
	{
		this.lineWidth = lineWidth;
	}
	else
	{
		this.lineWdith = 1.0;
	}
	
	this.render = function( context )
	{
		context.save();
	};
};

var Polygon = function( vertices, x, y, strokeStyle, fillStyle, lineWidth )
{
	this.super = Drawable;
	this.super(x, y);
	this.vertices = vertices;
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
		if ( vertices != null && this.vertices.length > 0 )
		{
			context.save();
			context.fillStyle   = this.fillStyle;
			context.strokeStyle = this.strokeStyle;
			context.lineWidth   = this.lineWidth;
			context.beginPath();
			context.moveTo( this.vertices[0][0], this.vertices[0][1] );
			for ( var i = 1; i < this.vertices.length; i++ )
			{
				context.lineTo( this.vertices[i][0], this.vertices[i][1] );
			}
			context.closePath();
			context.fill( );
			context.stroke( );
			context.restore( );
		}
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
		if (name in this.drawables)
		{
			this.removeDrawable( name );
		}
		this.drawables[name] = drawable;
		drawable.layer = this;
		this.drawablesCount++;
		this.invalidate();
	};
	
	this.removeDrawable = function ( name )
	{
		this.drawables[name] = null;
		delete this.drawables[name];
		this.drawablesCount--;
		this.invalidate();
	};
	
	this.clearDrawables = function ( )
	{
		this.drawables = { };
		this.drawablesCount = 0;
		this.invalidate();
	};
	
	this.drawables = { };
}

var Renderer = function ( context )
{
	this.context = context;
	this.layers = Array();
	this.intervalHandle = null;
	this.viewPortOffset = [ 0, 0 ];
	this.needsRepaint = true;
	
	this.loopInterval = 50;
	
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
			for( var i = 0; i < this.layers.length; i++ )
			{
				/*// a previous layer requested repainting
				if ( repaint )
				{
					// paint this layer over the previous one
					console.log('Repainting layer ' + i + ' as follow up.');
					this.layers[i].render( this.context, backgroundBuffer );
				}
				// this layer requests painting
				else if ( repaint = this.layers[i].needsRepaint )
				{
					// paint this layer using the previous' layers image buffer
					console.log('Repainting layer ' + i + ' as first (using backgroundBuffer).');
					this.layers[i].render( this.context, backgroundBuffer );
				}*/
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
						this.context.clearRect( this.viewPortOffset[0], this.viewPortOffset[1], this.context.canvas.width, this.context.canvas.width );
					}
				}
				if ( repaint = (this.layers[i].needsRepaint || repaint) )
				{
					//console.log('rendering image on layer ' + i);
					// if layer rendered something and it was not the last layer
					if ( this.layers[i].render( this.context ) && ( i < (this.layers.length - 1) ) )
					{
						//console.log('getting image data on layer ' + i);
						// save the new data
						this.layers[i].currentImageData = this.getImageData( this.context );
					}
					else
					{					
						this.layers[i].currentImageData = backgroundBuffer;
					}
				}
				backgroundBuffer = this.layers[i].currentImageData;
			}
			this.context.restore();
			this.needsRepaint = false;
		}
	};
	
	this.invalidate = function( )
	{
		this.needsRepaint = true;
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
};