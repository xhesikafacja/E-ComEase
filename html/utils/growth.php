<?php
    /* 
    * Growth logic
    * @param $db - database instance
    * @param $property - property to calculate growth
    * @param $condition - condition to filter the property, default is null
    */
    function calculateGrowth($db, $property, $condition = null) {
        $query = "SELECT COUNT($property) FROM sales";
    
        if ($condition !== null) {
            $column = $condition['column'];
            $initial = $condition['initial'];
            $final = $condition['final'];
    
            $query .= " WHERE $column BETWEEN ? AND ?";
            $result = $db->execute_query($query, [$initial, $final]);
        } else {
            $result = $db->execute_query($query);
        }
        return $result;
    }