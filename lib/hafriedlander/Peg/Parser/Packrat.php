<?php

namespace hafriedlander\Peg\Parser;

/**
 * By inheriting from Packrat instead of Parser,
 * the parser will run in linear time (instead of exponential like Parser),
 * but will require a lot more memory, since every match-attempt at every position is memorised.
 *
 * We now use a string as a byte-array to store position information
 * rather than a straight array for memory reasons.
 * This means there is a (roughly) 8MB limit on the size of the string we can parse
 *
 * @author Hamish Friedlander
 */
class Packrat extends Basic
{
    protected $packstatebase;
    protected $packstate;
    protected $packres;

    public function setSource($string)
    {
        parent::setSource($string);

        $max = unpack('N', "\x00\xFD\xFF\xFF");
        if (strlen($string) > $max[1]) user_error('Attempting to parse string longer than Packrat Parser can handle', E_USER_ERROR);

        $this->packstatebase = str_repeat("\xFF", strlen($string)*3);
        $this->packstate = array();
        $this->packres = array();
    }

    public function packhas($key, $pos)
    {
        $pos *= 3;
        return isset($this->packstate[$key])
            && isset($this->packstate[$key][$pos])
            && "\xFF" !== $this->packstate[$key][$pos];
    }

    public function packread($key, $pos)
    {
        $pos *= 3;
        if ("\xFE" === $this->packstate[$key][$pos]) return false;

        $this->pos = ord($this->packstate[$key][$pos]) << 16
            | ord($this->packstate[$key][$pos+1]) << 8
            | ord($this->packstate[$key][$pos+2]);
        return $this->packres["$key:$pos"];
    }

    public function packwrite($key, $pos, $res)
    {
        if (!isset($this->packstate[$key])) {
            $this->packstate[$key] = $this->packstatebase;
        }
        $pos *= 3;

        if (false !== $res) {
            $i = pack('N', $this->pos);
            $this->packstate[$key][$pos]   = $i[1];
            $this->packstate[$key][$pos+1] = $i[2];
            $this->packstate[$key][$pos+2] = $i[3];
            $this->packres["$key:$pos"] = $res;
        } else {
            $this->packstate[$key][$pos] = "\xFE";
        }

        return $res;
    }
}
