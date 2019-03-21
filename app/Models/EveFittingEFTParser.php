<?php

namespace App\Models;

/**
 * @section LICENSE
 * Copyright (c) 2013, Will Ross
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *     Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @file
 */

class EveFittingEFTParser {

    public static function EveFittingRender( $eftFit ) {
        /*
         *
         * $eftFit is the raw input output of the copy to pasteboard function
         * from EFT. The format is:
        [{ship_type}, {fit_name}]

        {low_slots}
        ...
        [Empty Low slot]

        {med_slots}
        ...
        [Empty Med slot]

        {high_slots}
        ...
        [Empty High slot]

        {rigs}
        ...
        [Empty Rig slot]

        {subsystems}
        ...
        [Empty Subsystem slot]


        {drones}

        * Text enclosed in braces is variable text. Unused slots/rigs are
        * specified by special tokens. There are two lines between the last
        * high slot (or subsystem on T3 cruisers) and the start of the drones
        * section.
        */

        // Normalize \r\n to \n
        $nixBreaks = str_replace( "\r\n", "\n", $eftFit );
        $ids = [];

        // Discard any manual line breaks already added
        $nobrs = str_replace( array( "<br />\n", "<br />" ), array( "\n" ),
            $nixBreaks );

        // split the fit into seperate lines
        $lines = explode( "\n", $nobrs );

        // The first line is the ship type and fitting name in a special format
        $firstLine = array_shift( $lines );
        // Trim the brackets off
        $trimmed = substr( $firstLine, 1, -1 );
        // Extract the ship name and the fit name
        list( $shipName, $fitName ) = explode( ", ", $trimmed );
        $shipID = Type::where('typeName', $shipName)->first();
        $shipID = ($shipID) ? $shipID->typeID : -1;
        $ids[] = $shipID;

        // Parse the items
        $sections = array();
        $charges = array();
        // Prime the section array
        $current = array();
        foreach ( $lines as $line ) {
            // If on a blank line, it's a divider between sections
            if ( $line == "" ) {
                // Add a blank new section
                $sections[] = $current;
                $current = array();
                continue;
            }
            // Split items from charges
            $exploded = explode( ", ", $line );
            // Drones count as a charge, but are signified by their quantity
            // being in the form 'Drone_Name x#'
            if ( preg_match( "/(.+) x([\d+])$/",
                    $exploded[0], $matches ) == 1 ) {
                // Drone
                $droneID = Type::where('typeName', $matches[1])->first();
                $droneID = ($droneID) ? $droneID->typeID : -1;
                $ids[] = $droneID;
                $quantity = intval( $matches[2] );
                // Add drones to the charges array
                for ( $i = 0; $i < $quantity; $i++ ) {
                    $charges[] = $droneID;
                }
            } else {
                // Get the itemID for the item name and save it for later
                $itemID = Type::where('typeName', $exploded[0])->first();
                $itemID = ($itemID) ? $itemID->typeID : -1;
                $ids[] = $itemID;
                if ( $itemID >= 0 ) {
                    $current[] = $itemID;
                }
                // Save the charge for the end
                if ( count( $exploded ) > 1 ) {
                    $chargeID = Type::where('typeName', $exploded[1])->first();
                    $ids[] = $chargeID;
                    if ( $chargeID >= 0 ) {
                        $charges[] = $chargeID;
                    }
                }
            }
        }
        $sections[] = $current;

        // If the ship type is invalid, making the DNA is unecessary
        if ( $shipID < 0 ) {
            return null;
        }

        // Remove empty sections
        $sections = array_filter( $sections );

        // T3 cruisers have subsystems, which will show up as an extra section
        // The numbers are the typeIDs for the four T3 cruisers.
        if ( $shipID == 29984 ||
            $shipID == 29986 ||
            $shipID == 29988 ||
            $shipID == 29990 ) {
            $subsystems = array_pop( $sections );
            foreach ( $subsystems as $subsystem ) {
                $ids[] = $subsystem;
            }
        }

        return array_unique($ids);
    }
}
