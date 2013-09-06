<?php
/**
 * A module for showing pages related to the current page
 *
 * @package inpage-modules
 */
class RelatedPagesModule extends ContentModule {

        public static $singular_name = 'Related Content';
        public static $plural_name = 'Related Content';

	public static $limit_top_results = 4;
        public static $limit_bottom_results = 30;

	public static $date_options = array('days', 'years', 'months', 'weeks');

        public static $db = array(
                'ContentTitle' => 'Text',
                'ShowSearch' => 'Boolean',
                'HasCategories' => 'Boolean',
                'ShowCategories' => 'Boolean',
                'UseChildren' => 'Boolean',
		'DisableSeeMore' => 'Boolean',
                'ItemStyle' => 'Varchar',
                'LimitTopResults' => 'Int',
                'LimitBottomResults' => 'Int',
		'DefaultLayout' => 'Enum("Tile, List", "Tile")',
        );

        public static $styles = array(
                'blue' => 'Blue',
                'violet' => 'Violet'
        );

	public static $has_one = array(
		'TopLevelPage' => 'Page'
	);

        public static $many_many = array(
                'Pages' => 'Page'
        );

        public static $many_many_extraFields = array(
                'Pages' => array(
                        'SortOrder' => 'Int(1)'
                )
        );

        public static $casting = array(
                'Keyword' => 'Varchar',
		'CategoryFilter' => 'Varchar',
		'DateFilter' => 'Varchar',
		'KeywordFilter' => 'Varchar'
        );

	public static $defaults = array(
		'DefaultLayout' => 'Tile',
                'LimitTopResults' => 4,
                'LimitBottomResults' => 30,
	);

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $categoryFields = array();
                $nonCategoryFields = array();

                $fields->addFieldToTab('Root.Main', new TextField('ContentTitle', 'Content Title'));
                $fields->addFieldToTab('Root.Main', new DropdownField('ItemStyle', 'Item Style', self::$styles));

		$fields->addFieldToTab('Root.Main', new DropdownField('DefaultLayout', 'Default Layout', $this->dbObject('DefaultLayout')->enumValues()));
                $fields->addFieldToTab('Root.Main', new NumericField('LimitTopResults', 'Limit Top Results - how many items to show at top (defaults to 4)'));
                $fields->addFieldToTab('Root.Main', new NumericField('LimitBottomResults', 'Limit Bottom Results - how many items to show at bottom (defaults to 30)'));

                //children fields
                $fields->addFieldsToTab('Root.Main', array(
                        $useChildren = new CheckboxField('UseChildren', 'Use page as category?', 1),
	                $categoryFields[] = $pageField = new ContentModuleTreeDropdownField('TopLevelPageID', 'Top Level Page', 'SiteTree'),
                        $categoryFields[] = $showSearch = new CheckboxField('ShowSearch', 'Show search?'),
                        $categoryFields[] = $hasCategories = new CheckBoxField('HasCategories', 'Has categories? (2 levels or single level)'),
                        $categoryFields[] = $showCategories = new CheckboxField('ShowCategories', 'Show category navigation bar? (for filtering)'),
                ));

	        $pageField->addExtraClass('use-children');
                $useChildren->addExtraClass('use-children');
                $showSearch->addExtraClass('show-search');
                $hasCategories->addExtraClass('has-categories');
                $showCategories->addExtraClass('show-categories');

                //add page fields
                $fields->addFieldsToTab('Root.Main', array(
                        $tree = new ContentModuleTreeDropdownField('NewPage', 'Add Page (choose from dropdown to add below)', 'SiteTree'),
			$enableSeeMore = new CheckboxField('DisableSeeMore', 'Disable "See more"?', 0),
                        $nonCategoryFields[] = $rEditor = new ContentModuleRelationshipEditor('PagesRelationEditor', 'Pages', 'Pages', $this, array(
                                'Title' => 'Title',
                                'Parent.Title' => 'Parent'
                        )),
                ));

                $rEditor->setSortField('SortOrder')
                        ->setShowDeleteButton(false);;

		//url for adding page
                $tree->setAttribute('data-add_url', 'addPage');
                $nonCategoryFields[] = $tree;

                //setup classes for hiding show fields
                foreach ($categoryFields as $categoryField) {
                        $categoryField->addExtraClass('category');
                }

                //setup classes for hiding show fields
                foreach ($nonCategoryFields as $categoryField) {
                        $categoryField->addExtraClass('non-category');
                }

	        Requirements::javascript(INPAGE_MODULES_DIR . '/javascript/RelatedPagesModuleCMS.js');

                return $fields;
        }

        public function Pages() {
                return $this->getManyManyComponents('Pages')->sort('SortOrder');
        }

        public function EditFields() {
                $fields = parent::EditFields();

                $tree = $fields->fieldByName('ContentModule[{$this->ID}][NewPage]');
                if ($tree) {
                        $tree->setValue(0);
                        $tree->setRecord(null);
                }

                return $fields;
        }

        public function EditForm() {
                $form = parent::EditForm();

                return $form;
        }

        public function onBeforeWrite() {
                parent::onBeforeWrite();
                $newID = 0;
                if (isset($_REQUEST['NewPage'])) $newID = (int)$_REQUEST['NewPage'];
                else if (isset($_REQUEST['ContentModule'][$this->ID]['NewPage'])) $newID = (int)$_REQUEST['ContentModule'][$this->ID]['NewPage'];


                if ($newID) {
                        $this->Pages()->add($newID);
                        $this->NewPage = 0;
                }
        }

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		//fix up pages which just used the parent
		$modules = RelatedPagesModule::get();

		if ($modules && $modules->count()) foreach ($modules as $module) {

			if ($module->UseChildren && !$module->TopLevelPageID) {
				//lookup page which has this module
				$pageID = DB::query('SELECT "PageID" FROM "Page_ContentModules" WHERE "ContentModuleID" = ' . $module->ID)->value();
				if ($pageID) {
					$module->TopLevelPageID = $pageID;
					$module->write();
					$module->publish('Stage', 'Live');
				}
			}
		}
	}

        public function getTopLimit() {
                return $this->LimitTopResults ? $this->LimitTopResults : self::$limit_top_results;
        }

        public function getBottomLimit() {
                return $this->LimitBottomResults ? $this->LimitBottomResults : self::$limit_bottom_results;
        }

        /**
         * Retrieve categories for the page this module is on
         */
        public function Categories() {
                $page = $this->TopLevelPage();

                $filterCategory = isset($_GET['Category'][$this->ID]) ? $_GET['Category'][$this->ID] : null;

                $categories = $page->Children();

                $aCategories = new ArrayList();
                if ($categories) foreach ($categories as $category) {
                        $aCategories->push(new ArrayData(array(
                                'Title' => $category->Title,
                                'Link' => HTTP::setGetVar("Category[{$this->ID}]", $category->URLSegment),
                                'LinkingMode' => ($filterCategory == $category->URLSegment) ? 'active' : 'inactive'
                        )));
                }

                return $aCategories;
        }

        /**
         * Get the list of pages to be displayed in the template
         * @return DataList
         */
        public function CurrentPages() {
                if ($this->ShowSearch) {
			if ($this->UseChildren) {
                        	$items = $this->FilterPages();
			} else {
				$items = $this->FilterPages()->sort('SortOrder');
			}
                }
                else {
			if ($this->UseChildren) {
                        	$items = $this->NormalPages();
			} else {
				$items = $this->NormalPages()->sort('SortOrder');
			}
                }


                if ($this->getTopLimit()) {
                        return PaginatedList::create($items, Controller::curr()->request)->setPageLength($this->getTopLimit());
                }

                return $items;
        }

        public function OtherPages() {
                if ($this->ShowSearch) {
			$items = $this->FilterPages();
                }
                else {
			$items = $this->NormalPages();
                }

                if ($this->getTopLimit()) {
                        $items->limit($this->getBottomLimit(), $this->getTopLimit());
                }

                return $items;
        }

        /**
         * Get the base pages before filtering etc
         * @return DataList
         */
        public function BasePages() {
                if ($this->UseChildren) {
                        return SiteTree::get()
				->innerJoin('SiteTree', '"SiteTree"."ParentID" = "Parent"."ID"', 'Parent')
				->sort('"Parent"."Sort" ASC');

                }
                return $this->Pages();
        }

        public function NormalPages() {
                $where = $this->getWhereFilter();

                $pages = $this->BasePages();

                if ($where) $pages->where($where);

                return $pages;
        }

        /**
         * Return filtered list of pages, used when searching is enabled
         * @return DataList
         */
        public function FilterPages() {
                $categoryFilter = $this->getCategoryFilter();
                $keywordFilter = $this->getKeywordFilter();
                $dateFilter = $this->getDateFilter();

                $where = $this->getWhereFilter($categoryFilter, $keywordFilter, $dateFilter);

                $pages = $this->BasePages();

                if ($where) $pages->where($where);

		//categories sort by date, otherwise alphabetical
		$pages = $categoryFilter ? $pages->sort('"LastEdited" DESC') : $pages->sort('"Title" ASC');

                return $pages;
        }

        public function getDateFilterLink() {
                return HTTP::setGetVar("Date[{$this->ID}]", 3);
        }

        public function getFormURL() {
                return Director::makeRelative($_SERVER['REQUEST_URI']);
        }

        public function getKeywordName() {
                return "Keyword[{$this->ID}]";
        }

        /**
         * Recursively get category pages
         *
         * @todo Maybe limit this to only one level of categories to limit recursiveness
         * @param SiteTree $page
         * @param array $ids
         */
        public function getChildIDs(SiteTree $page, &$ids, $top = true) {

                if ($this->HasCategories) {//multiple levels
                        $children = $page->AllChildren();
                        if ($children->count()) {
                                if (!$top) $ids[] = $page->ID;
                                $top = false;
                                foreach($children as $child) {
                                        if(in_array($child->ID, $ids)) continue;

                                        $this->getChildIDs($child, $ids, false);
                                }
                        }
                }
                else {//1 level
                        $ids[] = $page->ID;
                }
        }

        public function doAddPage($fields = array()) {
                if (isset($fields['NewPage']) && ($id = $fields['NewPage'])) {
                        $this->Pages()->add((int)$id);

                        return "Page added to \"{$this->Title}\" module";
                }
                return 'Page could not be found';
        }

        public function getWhereFilter($categoryFilter = null, $keywordFilter = null, $dateFilter = null) {

                $page = $this->TopLevelPage();

                $where = '';
                $and = '';

                //limit to only children
                if ($this->UseChildren) {
                        $ids = array();
                        $this->getChildIDs($page, $ids);
                        if ($ids) {
                                $in = implode(',', $ids);
                                $where = "\"SiteTree\".\"ParentID\" in ({$in})";
                                $and = ' AND ';
                        }
                }

                if ($categoryFilter) {
                        $category = SiteTree::get()->filter(array(
                                'URLSegment' => $categoryFilter,
                                'ParentID' => $page->ID
                        ))->first();

                        if ($category) {
                                $where .= "{$and}\"SiteTree\".\"ParentID\" = '{$category->ID}'";
                                $and = ' AND ';
                        }
                }

                if ($keywordFilter) {
			$keywordFilter = Convert::raw2sql($keywordFilter);
                        $where .= "{$and}\"SiteTree\".\"Content\" like '%{$keywordFilter}%' OR \"SiteTree\".\"Title\" like '%{$keywordFilter}%'";
                        $and = ' AND ';
                }

                if ($dateFilter) {
			$parts = explode('-', $dateFilter);

			if (count($parts) == 2) {
				$numeric = (int)$parts[0];
				$datePart = $parts[1];

				//default to days
				if (!in_array($datePart, self::$date_options)) {
					$dataPart = 'days';
				}

				$date = DBField::create_field('SS_Datetime', strtotime("-{$numeric} {$datePart}"))->getValue();
				$where .= "{$and}\"SiteTree\".\"LastEdited\" >= '{$date}'";
				$and = ' AND ';
			}

                }

                return $where;
        }

        public function getKeywordFilter() {
                return isset($_REQUEST['Keyword'][$this->ID]) ? $_REQUEST['Keyword'][$this->ID] : '';
        }

        public function getDateFilter() {
                return isset($_REQUEST['Date'][$this->ID]) ? $_REQUEST['Date'][$this->ID] : '';
        }

        public function getCategoryFilter() {
                return isset($_GET['Category'][$this->ID]) ? $_GET['Category'][$this->ID] : '';
        }

        public function getCacheKey() {
                return $this->ID . $this->getKeywordFilter() . $this->getCategoryFilter() . $this->getDateFilter() . $this->LastEdited . $this->Layout();
        }

	/**
	 * Returns List or Tile, returns personalisation setting first, then the module setting, defaults to tile
	 * @return string
	 */
	public function Layout() {
		if ($layout = Personalisation::get_layout_setting()) {
			if ($layout == Personalisation::LAYOUT_TILE) {
				return 'Tile';
			}
			else if ($layout == Personalisation::LAYOUT_LIST) {
				return 'List';
			}
		}

		if ($this->DefaultLayout) return $this->DefaultLayout;

		return 'Tile';

	}
}