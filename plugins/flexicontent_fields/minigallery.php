<?php
/**
 * @version 1.0 $Id$
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.file
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

//jimport('joomla.plugin.plugin');
jimport('joomla.event.plugin');

class plgFlexicontent_fieldsMinigallery extends JPlugin
{
	function plgFlexicontent_fieldsMinigallery( &$subject, $params )
	{
		parent::__construct( $subject, $params );
        JPlugin::loadLanguage('plg_flexicontent_fields_minigallery', JPATH_ADMINISTRATOR);
	}

	function onDisplayField(&$field, $item)
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'minigallery') return;

		// some parameter shortcuts
		$size				= $field->parameters->get( 'size', 30 ) ;
						
		$document		= & JFactory::getDocument();
		$flexiparams 	=& JComponentHelper::getParams('com_flexicontent');
		$mediapath		= $flexiparams->get('media_path', 'components/com_flexicontent/media');
				

		$js = "
		function randomString() {
			var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz';
			var string_length = 6;
			var randomstring = '';
			for (var i=0; i<string_length; i++) {
				var rnum = Math.floor(Math.random() * chars.length);
				randomstring += chars.substring(rnum,rnum+1);
			}
			return randomstring;
		}

		function qfSelectFile".$field->id."(id, file) {
		
			var name 	= 'a_name'+id;
			var ixid 	= randomString();			
			var li 		= document.createElement('li');
			var thumb	= document.createElement('img');
			var hid		= document.createElement('input');
			var span	= document.createElement('span');
			var img		= document.createElement('img');
			
			var filelist = document.getElementById('sortables_".$field->id."');
			
			$(li).addClass('minigallery');
			$(thumb).addClass('thumbs');
			$(span).addClass('drag');
			
			var button = document.createElement('input');
			button.type = 'button';
			button.name = 'removebutton_'+id;
			button.id = 'removebutton_'+id;
			$(button).addClass('fcbutton');
			$(button).addEvent('click', function() { deleteField".$field->id."(this) });
			button.value = '".JText::_( 'FLEXI_REMOVE_FILE' )."';
			
			thumb.src = '../components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=../../../../components/com_flexicontent/medias/'+file+'&w=100&h=100&zc=1';
			thumb.alt ='".JText::_( 'FLEXI_CLICK_TO_DRAG' )."';
			
			hid.type = 'hidden';
			hid.name = '".$field->name."['+ixid+']';
			hid.value = id;
			hid.id = ixid;
			
			img.src = 'components/com_flexicontent/assets/images/move3.png';
			img.alt ='".JText::_( 'FLEXI_CLICK_TO_DRAG' )."';
			
			filelist.appendChild(li);
			li.appendChild(thumb);
			li.appendChild(button);
			li.appendChild(hid);
			li.appendChild(span);
			span.appendChild(img);
			
			new Sortables($('sortables_".$field->id."'), {
				'handles': $('sortables_".$field->id."').getElements('span.drag'),
				'onDragStart': function(element, ghost){
					ghost.setStyles({
					'list-style-type': 'none',
					'opacity': 1
					});
					element.setStyle('opacity', 0.3);
				},
				'onDragComplete': function(element, ghost){
					element.setStyle('opacity', 1);
					ghost.remove();
					this.trash.remove();
				}
			});			
		}
		
			function deleteField".$field->id."(el) {
				var field	= $(el);
				var row		= field.getParent();
				var fx		= row.effects({duration: 300, transition: Fx.Transitions.linear});
				
				fx.start({
					'height': 0,
					'opacity': 0			
				}).chain(function(){
					row.remove();
				});
			}
		";
		$document->addScriptDeclaration($js);

			//add the drag and drop sorting feature
			$js = "
			window.addEvent('domready', function(){
				new Sortables($('sortables_".$field->id."'), {
					'handles': $('sortables_".$field->id."').getElements('span.drag'),
					'onDragStart': function(element, ghost){
						ghost.setStyles({
						   'list-style-type': 'none',
						   'opacity': 1
						});
						element.setStyle('opacity', 0.3);
					},
					'onDragComplete': function(element, ghost){
						element.setStyle('opacity', 1);
						ghost.remove();
						this.trash.remove();
					}
					});			
				});
			";
			$document->addScriptDeclaration($js);


			$css = '
			#sortables_'.$field->id.' { margin: 0 0 10px 0; padding: 0px; list-style: none; white-space: nowrap; }
			#sortables_'.$field->id.' li {
				list-style: none;
				height: 100px;
				padding-top:10px;
				}
			#sortables_'.$field->id.' li img.thumbs {
				border: 1px solid silver;
				padding: 0;
				margin: 0 0 -5px 0;
				}
			#sortables_'.$field->id.' li input { cursor: text;}
			#sortables_'.$field->id.' li input.fcbutton, .fcbutton { cursor: pointer; margin-left: 3px; }
			span.drag img {
				margin: -4px 8px;
				cursor: move;
			}
			';
			$document->addStyleDeclaration($css);

			$move 	= JHTML::image ( 'administrator/components/com_flexicontent/assets/images/move3.png', JText::_( 'FLEXI_CLICK_TO_DRAG' ) );
				
		JHTML::_('behavior.modal', 'a.modal_'.$field->id);

		$i = 0;
		$field->html = '<ul id="sortables_'.$field->id.'">';
		
		if($field->value) {
			
			foreach($field->value as $file) {
				$field->html .= '<li>';
				
				$filename = $this->getFileName( $file );
				$src = '../components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=../../../../' . $mediapath . DS . $filename->filename . '&w=100&h=100&zc=1';

				$field->html .= '<img class="thumbs" src="'.$src.'"/>';
				$field->html .= '<input type="hidden" id="a_id'.$i.'" name="'.$field->name.'['.$i.']" value="'.$file.'" />';
				$field->html .= '<input class="inputbox fcbutton" type="button" onclick="deleteField'.$field->id.'(this);" value="'.JText::_( 'FLEXI_REMOVE_FILE' ).'" />';
				$field->html .= '<span class="drag">'.$move.'</span>';
				$field->html .= '</li>';
				$i++;
			}
		}

		$linkfsel = 'index.php?option=com_flexicontent&amp;view=fileselement&amp;tmpl=component&amp;layout=image&amp;filter_secure=M&amp;index='.$i.'&amp;field='.$field->id;
		$field->html .= "
						</ul>
						<div class=\"button-add\">
							<div class=\"blank\">
								<a class=\"modal_".$field->id."\" title=\"".JText::_( 'FLEXI_ADD_FILE' )."\" href=\"".$linkfsel."\" rel=\"{handler: 'iframe', size: {x: 850, y: 575}}\">".JText::_( 'FLEXI_ADD_FILE' )."</a>
							</div>
						</div>
						";
	}


	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'minigallery') return;

		$values = $values ? $values : $field->value ;
		
		global $mainframe;
		
		$document		= & JFactory::getDocument();
		$flexiparams 	=& JComponentHelper::getParams('com_flexicontent');
		$mediapath		= $flexiparams->get('media_path', 'components/com_flexicontent/media');

		// some parameter shortcuts
		$thumbposition		= $field->parameters->get( 'thumbposition', 3 ) ;
		$w_l				= $field->parameters->get( 'w_l', 450 ) ;
		$h_l				= $field->parameters->get( 'h_l', 300 ) ;
		$w_s				= $field->parameters->get( 'w_s', 100 ) ;
		$h_s				= $field->parameters->get( 'h_s', 66 ) ;
		
		switch ($thumbposition) {
			case 1: // top
			$marginpos = 'top';
			break;

			case 2: // left
			$marginpos = 'left';
			break;

			case 4: // right
			$marginpos = 'right';
			break;

			case 3:
			default : // bottom
			$marginpos = 'bottom';
			break;
		}

		if ($values)
		{
			$document->addStyleSheet('plugins/flexicontent_fields/minigallery/galleriffic.css');
			// this allows you to override the default css files
			$document->addStyleSheet(JURI::base().'/templates/'.$mainframe->getTemplate().'/css/minigallery.css');
			JHTML::_('behavior.mootools');
			$document->addScript('plugins/flexicontent_fields/minigallery/jquery.opacityrollover.js');
			$document->addScript('plugins/flexicontent_fields/minigallery/jquery.galleriffic.js');

		  	$js = "
		  	(function($){
		  		$(document).ready(function(){
					var obj = {
						wait: ".$field->parameters->get( 'wait', 4000 ).", 
						effect: '".$field->parameters->get( 'effect', 'fade' )."',
						direction: '".$field->parameters->get( 'direction', 'right' )."',
						duration: ".$field->parameters->get( 'duration', 1000 ).", 
						loop: ".$field->parameters->get( 'loop', 'true' ).", 
						thumbnails: true,
						backgroundSlider: true
					}
					//$('#slideshowContainer').slideshow($('#slideshowThumbnail'),obj);
					//show = new SlideShow('slideshowContainer','slideshowThumbnail',obj);
					//show.play();
					
					// We only want these styles applied when javascript is enabled
					$('div.navigation').css({'width' : '300px', 'float' : 'left'});
					$('div.content').css('display', 'block');
	
					// Initially set opacity on thumbnails and add
					// additional styling for hover effect on thumbnails
					var onMouseOutOpacity = 0.67;
					$('#thumbnails ul.thumbs li').opacityrollover({
						mouseOutOpacity:   onMouseOutOpacity,
						mouseOverOpacity:  1.0,
						fadeSpeed:         'fast',
						exemptionSelector: '.selected'
					});
					
					// Initialize Advanced Galleriffic Gallery
					var gallery = $('#thumbnails').galleriffic({
						delay:                     2500,
						numThumbs:                 ".count($values).",
						rel:					   'slideshow',
						preloadAhead:              10,
						enableTopPager:            true,
						enableBottomPager:         true,
						maxPagesToShow:            7,
						imageContainerSel:         '#slideshow',
						controlsContainerSel:      '#controls',
						captionContainerSel:       '#caption',
						loadingContainerSel:       '#loading',
						renderSSControls:          false,
						renderNavControls:         false,
						playLinkText:              'Play Slideshow',
						pauseLinkText:             'Pause Slideshow',
						prevLinkText:              '&lsaquo; Previous Photo',
						nextLinkText:              'Next Photo &rsaquo;',
						nextPageLinkText:          'Next &rsaquo;',
						prevPageLinkText:          '&lsaquo; Prev',
						enableHistory:             false,
						autoStart:                 false,
						syncTransitions:           true,
						defaultTransitionDuration: 900,
						onBeforeSlideChange:             function(prevIndex, nextIndex) {
							// 'this' refers to the gallery, which is an extension of $('#thumbnails')
							this.find('ul.thumbs').children()
								.eq(prevIndex).fadeTo('fast', onMouseOutOpacity).end()
								.eq(nextIndex).fadeTo('fast', 1.0);
						},
						onPageTransitionOut:       function(callback) {
							this.fadeTo('fast', 0.0, callback);
						},
						onPageTransitionIn:        function() {
							this.fadeTo('fast', 1.0);
						}
					});					
				});
			})(jQuery);	
			";
			$document->addScriptDeclaration($js);
			
			$css = "
			.slideshowContainer {
				width: ".$w_l."px;
				height: ".$h_l."px;
				margin-".$marginpos.": 5px;
			}
			";
	
			if ($thumbposition == 1 || $thumbposition == 3) {
				$css .= "#thumbnails { width: ".$w_l."px; }";
			}
			if ($thumbposition == 2 || $thumbposition == 4) {
				$css .= ".slideshowContainer { float: none; } #thumbnails { float: left; width: ".($w_s + 10)."px; }";
			}

			$document->addStyleDeclaration($css);
			
			$display = array();
			$field->{$prop} .= ($thumbposition > 2) ? '<div id="slideshowContainer" class="slideshowContainer">
				<div id="controls" class="controls"></div>
				<div class="slideshow-container">
					<div id="loading" class="loader"></div>
					<div id="slideshow" class="slideshow"></div>
				</div>
				<div id="caption" class="caption-container"></div>
			</div>' : '';
			$field->{$prop} .= '<div id="thumbnails" class="navigation"><ul class="thumbs noscript">';
			$n = 0;
			foreach ($values as $value) {
				$filename = $this->getFileName( $value );
				if ($filename) {
					$srcs 		= 'components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . JURI::base(true) . '/' . $mediapath . '/' . $filename->filename . '&w='.$w_s.'&h='.$h_s.'&zc=1';
					$srcb 		= 'components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . JURI::base(true) . '/' . $mediapath . '/' . $filename->filename . '&w='.$w_l.'&h='.$h_l.'&zc=1';
					
					$display[]	= '<li><a name="'.$filename->filename.'" title="' . JURI::base(true) . '/' . $mediapath . '/' . $filename->filename . '" href="'.$srcb.'" rel="slideshow" class="slideshowThumbnail thumb"><img src="'.$srcs.'" border="0" alt="' . JURI::base(true) . '/' . $mediapath . '/' . $filename->filename . '" /></a><div class="caption">
					</div></li>';
				}
				$n++;
				}
			$field->{$prop} .= implode(' ', $display);
			$field->{$prop} .= '</ul></div><div style="clear:both"></div>';
			$field->{$prop} .= ($thumbposition < 3) ? '<div id="slideshowContainer" class="slideshowContainer"></div>' : '';
		}
	}
	

	function onBeforeSaveField(&$field, &$post, $file)
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'minigallery') return;

		global $mainframe;
		
/*
		$newpost = array();
		
		for ($n=0, $c=count($post); $n<$c; $n++)
		{
			if ($post[$n] != '') $newpost[] = $post[$n];
		}
*/
		
		$post = array_unique($post);
	}


	function getFileName( $value )
	{
		$db =& JFactory::getDBO();

		$query = 'SELECT filename, altname, ext'
				. ' FROM #__flexicontent_files'
				. ' WHERE id = '. (int) $value
				;
		$db->setQuery($query);
		$filename = $db->loadObject();

		return $filename;
	}
	

	function addIcon( &$file )
	{
		switch ($file->ext)
		{
			// Image
			case 'jpg':
			case 'png':
			case 'gif':
			case 'xcf':
			case 'odg':
			case 'bmp':
			case 'jpeg':
				$file->icon = 'components/com_flexicontent/assets/images/mime-icon-16/image.png';
			break;

			// Non-image document
			default:
				$icon = JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'assets'.DS.'images'.DS.'mime-icon-16'.DS.$file->ext.'.png';
				if (file_exists($icon)) {
					$file->icon = 'components/com_flexicontent/assets/images/mime-icon-16/'.$file->ext.'.png';
				} else {
					$file->icon = 'components/com_flexicontent/assets/images/mime-icon-16/unknown.png';
				}
			break;
		}
		return $file;
	}
}