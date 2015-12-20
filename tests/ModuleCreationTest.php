<?php
/**
 * @package inpage-modules
 * @subpackage tests
 */

class ModuleCreationTest extends SapphireTest
{


    /*public function testCreateDefaultpages() {
        $remove = DataObject::get('SiteTree');
        if($remove) foreach($remove as $page) $page->delete();
        // Make sure the table is empty
        $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

        // Disable the creation
        SiteTree::set_create_default_pages(false);
        singleton('SiteTree')->requireDefaultRecords();

        // The table should still be empty
        $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

        // Enable the creation
        SiteTree::set_create_default_pages(true);
        singleton('SiteTree')->requireDefaultRecords();

        // The table should now have three rows (home, about-us, contact-us)
        $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 3);
    }*/

    public function testBenefitsModule()
    {
        $module = BenefitsModule::create();
        $module->Title = 'Test Benefits Module';
        $module->ContentTitle = 'Test';
        $module->write();

        $this->assertTrue($module->ID > 0);

        $item = BenefitItem::create();
        $item->Title = 'Test';
        $item->Text = 'Test';
        $item->write();

        $module->Benefits()->add($item);

        $this->assertTrue($item->ID > 0);
        $this->assertEquals($module->Benefits()->count(), 1);
    }


    public function testImageModule()
    {
        $module = ImageModule::create();
        $module->Title = 'Test';
        $module->ResizeMethod = 'SetWidth';
        $module->ResizeWidth = 900;
        $module->ResizeHeight = 600;

        $module->write();

        $this->assertTrue($module->ID > 0);

        $image = Image::create();
        $image->Title = 'Test';
        $image->write();
        $module->Images()->add($image);

        $this->assertEquals($module->Images()->count(), 1);
    }

    public function testSearchModule()
    {
        $module = SearchModule::create();
        $module->Title = 'Test';
        $module->ContentTitle = 'Test';
        $module->SearchTitle = 'Test';
        $module->SearchTags = 'Test';

        $module->write();
        $this->assertTrue($module->ID > 0);
    }


    public function testRelatedPagesModule()
    {
        $module = RelatedPagesModule::create();
        $module->ShowSearch = true;
        $module->HasCategories = true;
        $module->ShowCategories = true;
        $module->UseChildren = true;
        $module->write();

        $this->assertTrue($module->ID > 0);

        $page = Page::create();
        $page->Title = 'Test';
        $page->write();
        $module->Pages()->add($page);

        $this->assertEquals($module->Pages()->count(), 1);
    }

    public function testTextColumnsModule()
    {
        $module = TextColumnsModule::create();
        $module->ContentTitle = 'Test';
        $module->write();

        $this->assertTrue($module->ID > 0);

        $i = TextColumn::create();
        $i->Title = 'Test';
        $i->Text = 'Text';
        $i->Sort = 4;
        $i->write();
        $module->Columns()->add($i);

        $this->assertEquals($module->Columns()->count(), 1);
    }

    public function testTextModule()
    {
        $module = SearchModule::create();
        $module->Title = 'Test';
        $module->ContentTitle = 'Test';
        $module->Summary = 'Text';
        $module->write();

        $this->assertTrue($module->ID > 0);
    }

    public function testTwoLinksModule()
    {
        $module = TwoLinksModule::create();
        $module->Title = 'Test';
        $module->ContentTitle = 'Test';
        $module->write();
        $this->assertTrue($module->ID > 0);

        $i = TwoLinksLink::create();
        $i->Title = 'Test';
        $i->Text = 'Text';

        $p = Page::create();
        $p->Title = 'Text';
        $p->write();

        $i->PageID = $p->ID;
        $module->Links()->add($i);

        $this->assertEquals($p->ID, $i->Page()->ID);
        $this->assertEquals($module->Links()->count(), 1);
    }
}
