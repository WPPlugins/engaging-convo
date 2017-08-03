<?php
/**
 * Thread Collection class.
 *
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Enco_Thread_Collection class.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Thread_Collection extends Enco_Collection {

    /**
     * Create a new Enco_Thread_Collection
     *
     * @param array $items
     * @return void
     */
    public function __construct(array $items = []) {
        
        parent::__construct( $items );

    }

    /**
     * Load the document
     *
     * @param Enco_Document $document
     * @return void
     */
    public function document( $document ) {

        if( $document != null ) {
            $this->document = $document;
        }
    }

    public function sort_by_total() {

        $temp = array();
        $sorted = array();

        foreach ( $this->items as $item ) {
            $id         = $item->ID;
            $total      = $item->total();
            $temp[$id]  = $total;
        }

        arsort($temp);

        foreach ( $temp as $id => $value ) {
            $sorted[$id] = $this->items[$id];
        }

        $this->items = $sorted;

        return $sorted;
    }
}