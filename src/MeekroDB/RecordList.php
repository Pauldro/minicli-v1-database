<?php namespace Pauldro\Minicli\Database\MeekroDB;
// ProcessWire
use Pauldro\Minicli\Util\DataArray;

/**
 * Container for Lists of Records
 */
class RecordList extends DataArray {
    public function makeBlankItem() : Record
    {
        return new Record();
    }
}