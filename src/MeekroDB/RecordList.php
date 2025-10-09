<?php namespace Pauldro\Minicli\Database\MeekroDB;
// ProcessWire
use Pauldro\Minicli\Util\DataArray;
use Pauldro\Minicli\Util\Data;

/**
 * Container for Lists of Records
 */
class RecordList extends DataArray {
    public function makeBlankItem() : Data
    {
        return new Record();
    }
}