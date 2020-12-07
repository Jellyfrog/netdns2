<?php

/**
 * DNS Library for handling lookups and updates. 
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @category  Networking
 * @package   NetDNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.4.1
 *
 */

namespace NetDNS2\RR;

/**
 * CSYNC Resource Record - RFC 7477 seciond 2.1.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  SOA Serial                   |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    Flags                      |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                 Type Bit Map                  /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CSYNC extends \NetDNS2\RR
{
    /*
     * serial number
     */
    public $serial;

    /*
     * flags
     */
    public $flags;

    /*
     * array of RR type names
     */
    public $type_bit_maps = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $out = $this->serial . ' ' . $this->flags;

        //
        // show the RR's
        //
        foreach ($this->type_bit_maps as $rr) {

            $out .= ' ' . strtoupper($rr);
        }

        return $out;
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return boolean
     * @access protected
     *
     */
    protected function rrFromString(array $rdata)
    {
        $this->serial   = array_shift($rdata);
        $this->flags    = array_shift($rdata);

        $this->type_bit_maps = $rdata;

        return true;
    }

    /**
     * parses the rdata of the \NetDNS2\Packet object
     *
     * @param \NetDNS2\Packet &$packet a \NetDNS2\Packet packet to parse the RR from
     *
     * @return boolean
     * @access protected
     *
     */
    protected function rrSet(\NetDNS2\Packet &$packet)
    {
        if ($this->rdlength > 0) {

            //
            // unpack the serial and flags values
            //
            $x = unpack('@' . $packet->offset . '/Nserial/nflags', $packet->rdata);

            $this->serial   = \NetDNS2\Client::expandUint32($x['serial']);
            $this->flags    = $x['flags'];

            //
            // parse out the RR bitmap                 
            //
            $this->type_bit_maps = \NetDNS2\BitMap::bitMapToArray(
                substr($this->rdata, 6)
            );

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param \NetDNS2\Packet &$packet a \NetDNS2\Packet packet use for
     *                                 compressed names
     *
     * @return mixed                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet(\NetDNS2\Packet &$packet)
    {
        //
        // pack the serial and flags values
        //
        $data = pack('Nn', $this->serial, $this->flags);

        //
        // convert the array of RR names to a type bitmap
        //
        $data .= \NetDNS2\BitMap::arrayToBitMap($this->type_bit_maps);

        //
        // advance the offset
        //
        $packet->offset += strlen($data);

        return $data;
    }
}