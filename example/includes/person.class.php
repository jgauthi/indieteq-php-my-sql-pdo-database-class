<?php
use Jgauthi\Component\Database\AbstractCrud;

class Person extends AbstractCrud
{
    // Your Table name
    protected string $table = 'persons';

    // Primary Key of the Table
    protected string $pk = 'id';

    public function create()
    {
        $this->variables['last_update'] = null;
        return parent::create();
    }

    public function save($id = null)
    {
        // Save Date Update
        $this->variables['last_update'] = new DateTime;
        return parent::save($id);
    }
}
