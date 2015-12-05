// window load because we want to fully load images to see sizes and such


$(window).load(function() {
	
	$("#target").one('load', function() {
		  // do stuff
		}).each(function() {

			function delayed() { setTimeout(function() { 

				  // Create variables (in this scope) to hold the API and
					// image size
				    var jcrop_api,
				        boundx,
				        boundy,

				        // Grab some information about the preview pane
				        $preview = $('#preview-pane'),
				        $pcnt = $('#preview-pane .preview-container'),
				        $pimg = $('#preview-pane .preview-container img'),

				        xsize = $pcnt.width(),
				        ysize = $pcnt.height(),

				        rxsize = $pimg.width(),
				        rysize = $pimg.height();
				    
				    $('#target').Jcrop({
				      onChange: updatePreview,
				      onSelect: updatePreview,
				      aspectRatio: php_jcropSetAspectRatio,
				      setSelect:   [ 0, 0, php_jcropSetSelectX, php_jcropSetSelectY ]
				    },function(){
				      // Use the API to get the real image size
				      var bounds = this.getBounds();
				      boundx = bounds[0];
				      boundy = bounds[1];
				      // Store the API in the jcrop_api variable
				      jcrop_api = this;

				      // Move the preview into the jcrop container for css
						// positioning
				      $preview.appendTo(jcrop_api.ui.holder);
				    });

				    function updatePreview(c)
				    {
				       
				  	  var scaledWidth = rxsize / $('.jcrop-holder > img').width();
				      var scaledHeight = rysize / $('.jcrop-holder > img').height();
				      
				    	$('#x').val(Math.round(c.x * scaledWidth));
				        $('#y').val(Math.round(c.y * scaledHeight));
				        $('#w').val(Math.round(c.w * scaledWidth));
				        $('#h').val(Math.round(c.h * scaledHeight));

				      if (parseInt(c.w) > 0)
				      {
				        var rx = xsize / c.w;
				        var ry = ysize / c.h;

				        var saspect = xsize / ysize  ;
				        var raspect = rxsize / rysize;

				        $pimg.css({
				          width: Math.round(rx * boundx) + 'px',
				          height: Math.round(ry * boundy) + 'px',
				          marginLeft: '-' + Math.round(rx * c.x) + 'px',
				          marginTop: '-' + Math.round(ry * c.y) + 'px'
				        });

				        
				      }
				    };
				    
			  // }
				
			}, 200); }
			
			delayed();
			
		});
});
