<?php
/**
 * @version 1.5 stable $Id: view.html.php 371 2010-07-21 06:50:10Z enjoyman $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 * 
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

/**
 * HTML View class for the FLEXIcontent View
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentViewCategory extends JView
{
	/**
	 * Creates the Forms for the View
	 *
	 * @since 1.0
	 */
	function display( $tpl = null )
	{
		global $mainframe, $option;

		JHTML::_('behavior.tooltip');

		//initialize variables
		$document 	= & JFactory::getDocument();
		$menus		= & JSite::getMenu();
		$menu    	= $menus->getActive();
		$params 	= & $mainframe->getParams('com_flexicontent');
		$uri 		= & JFactory::getURI();
		$dispatcher	= & JDispatcher::getInstance();
		$user		= & JFactory::getUser();
		$aid		= (int) $user->get('aid');

		// Request variables
		$limitstart		= JRequest::getInt('limitstart');
		$format			= JRequest::getVar('format', null);
		
		//add css file
		if (!$params->get('disablecss', '')) {
			$document->addStyleSheet($this->baseurl.'/components/com_flexicontent/assets/css/flexicontent.css');
			$document->addCustomTag('<!--[if IE]><style type="text/css">.floattext {zoom:1;}</style><![endif]-->');
		}
		//allow css override
		if (file_exists(JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'css'.DS.'flexicontent.css')) {
			$document->addStyleSheet($this->baseurl.'/templates/'.$mainframe->getTemplate().'/css/flexicontent.css');
		}

		//pathway
		$pathway 	= & $mainframe->getPathWay();

		// Get data from the model		
		$category 	= & $this->get('Category');
		$categories	= & $this->get('Childs');
		$items 		= & $this->get('Data');
		$filters 	= & $this->get('Filters');
		$alpha	 	= & $this->get('Alphaindex');
		$model		= & $this->getModel();
		
		$cparams	=& $category->parameters;
		$params->merge($cparams);

		$total 		= & $this->get('Total');

		// Bind Fields
		if ($format != 'feed') {
			$items 	= FlexicontentFields::getFields($items, 'category', $params, $aid);
		}

		//Set layout
		$this->setLayout('category');

		$limit		= $mainframe->getUserStateFromRequest('com_flexicontent'.$category->id.'.category.limit', 'limit', $params->def('limit', 0), 'int');

		$cats		= new flexicontent_cats((int)$category->id);
		$parents	= $cats->getParentlist();
		
		// We can probaly optimize this part later
		if ($params->get('rootcat')) {
			$rootcats	= new flexicontent_cats((int)$params->get('rootcat'));
			$allroots	= $rootcats->getParentlist();
			$roots		= array();
			foreach ($allroots as $root) {
				array_push($roots, $root->id);
			}
		}
		
		// because the application sets a default page title, we need to get it
		// right from the menu item itself
		if (is_object( $menu )) {
			$menu_params = new JParameter( $menu->params );		
			
			if (!$menu_params->get( 'page_title')) {
				$params->set('page_title',	$category->title);
			}
		} else {
			$params->set('page_title',	$category->title);
		}
		
		// pathway construction @TODO try to find and automated solution
		for($p=$params->get('item_depth', 0); $p<count($parents); $p++) {
			// Do not add the above and root categories when coming from a directory view
			if (isset($allroots)) {
				if (!in_array($parents[$p]->id, $roots)) {
					$pathway->addItem( $this->escape($parents[$p]->title), JRoute::_( FlexicontentHelperRoute::getCategoryRoute($parents[$p]->categoryslug) ) );
				}
			} else {
				$pathway->addItem( $this->escape($parents[$p]->title), JRoute::_( FlexicontentHelperRoute::getCategoryRoute($parents[$p]->categoryslug) ) );
			}
		}


		$document->setTitle( $params->get( 'page_title' ) );

		if ($mainframe->getCfg('MetaTitle') == '1') {
				$mainframe->addMetaTag('title', $category->title);
		}
		
		if ($params->get('show_feed_link', 1) == 1) {
			//add alternate feed link
			$link	= '&format=feed';
			$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
			$document->addHeadLink(JRoute::_($link.'&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
			$document->addHeadLink(JRoute::_($link.'&type=atom'), 'alternate', 'rel', $attribs);
		}
		
		$themes		= flexicontent_tmpl::getTemplates();
		
		if ($params->get('clayout')) {
			// Add the templates css files if availables
			if (isset($themes->category->{$params->get('clayout')}->css)) {
				foreach ($themes->category->{$params->get('clayout')}->css as $css) {
					$document->addStyleSheet($this->baseurl.'/'.$css);
				}
			}
			// Add the templates js files if availables
			if (isset($themes->category->{$params->get('clayout')}->js)) {
				foreach ($themes->category->{$params->get('clayout')}->js as $js) {
					$document->addScript($this->baseurl.'/'.$js);
				}
			}
			// Set the template var
			$tmpl = $themes->category->{$params->get('clayout')}->tmplvar;
		} else {
			$tmpl = '.category.default';
		}

		JPluginHelper::importPlugin('content');
		// just a try : to implement later
		foreach ($items as $item) {
			$item->event = new stdClass();
			$item->params = new JParameter($item->attribs);
			$results = $dispatcher->trigger('onPrepareContent', array (& $item, & $item->params,0));
			$item->event->afterDisplayTitle = trim(implode("\n", $results));

			$results = $dispatcher->trigger('onAfterDisplayTitle', array (& $item, & $item->params,0));
			$item->event->afterDisplayTitle = trim(implode("\n", $results));

			$results = $dispatcher->trigger('onBeforeDisplayContent', array (& $item, & $item->params, 0));
			$item->event->beforeDisplayContent = trim(implode("\n", $results));
	
			$results = $dispatcher->trigger('onAfterDisplayContent', array (& $item, & $item->params, 0));
			$item->event->afterDisplayContent = trim(implode("\n", $results));
		}


		// remove previous alpha index filter
		$uri->delVar('letter');

		//ordering
		$filter_order		= JRequest::getCmd('filter_order', 'i.title');
		$filter_order_Dir	= JRequest::getCmd('filter_order_Dir', 'ASC');
		$filter				= JRequest::getString('filter');
		
		$lists						= array();
		$lists['filter_order']		= $filter_order;
		$lists['filter_order_Dir'] 	= $filter_order_Dir;
		$lists['filter']			= $filter;

		// Add html to filter object
		if ($filters)
		{
			// Make the filter compatible with Joomla standard cache
			$cache = JFactory::getCache('com_flexicontent');
			$cache->clean();

			foreach ($filters as $filtre)
			{
				$value		= $mainframe->getUserStateFromRequest( $option.'.category'.$category->id.'.filter_'.$filtre->id, 'filter_'.$filtre->id, '', 'cmd' );
				$results 	= $dispatcher->trigger('onDisplayFilter', array( &$filtre, $value ));
				$lists['filter_' . $filtre->id] = $value;
			}
		}

		// Create the pagination object
		jimport('joomla.html.pagination');

		$pageNav 	= new JPagination($total, $limitstart, $limit);

		$this->assign('action', 			$uri->toString());

		$print_link = JRoute::_('index.php?view=category&cid='.$category->slug.'&pop=1&tmpl=component');
		
		$this->assignRef('params' , 		$params);
		$this->assignRef('categories' , 	$categories);
		$this->assignRef('items' , 			$items);
		$this->assignRef('category' , 		$category);
		$this->assignRef('limitstart' , 	$limitstart);
		$this->assignRef('pageNav' , 		$pageNav);
		$this->assignRef('filters' ,	 	$filters);
		$this->assignRef('lists' ,	 		$lists);
		$this->assignRef('alpha' ,	 		$alpha);
		$this->assignRef('tmpl' ,			$tmpl);
		$this->assignRef('print_link' ,		$print_link);

		/*
		 * Set template paths : this procedure is issued from K2 component
		 *
		 * "K2" Component by JoomlaWorks for Joomla! 1.5.x - Version 2.1
		 * Copyright (c) 2006 - 2009 JoomlaWorks Ltd. All rights reserved.
		 * Released under the GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
		 * More info at http://www.joomlaworks.gr and http://k2.joomlaworks.gr
		 * Designed and developed by the JoomlaWorks team
		 */
		$this->addTemplatePath(JPATH_COMPONENT.DS.'templates');
		$this->addTemplatePath(JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_flexicontent'.DS.'templates');
		$this->addTemplatePath(JPATH_COMPONENT.DS.'templates'.DS.'default');
		$this->addTemplatePath(JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_flexicontent'.DS.'templates'.DS.'default');
		if ($params->get('clayout')) {
			$this->addTemplatePath(JPATH_COMPONENT.DS.'templates'.DS.$params->get('clayout'));
			$this->addTemplatePath(JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_flexicontent'.DS.'templates'.DS.$params->get('clayout'));
		}

		parent::display($tpl);

	}
}
?>