function select_query($table, $fields, $where, $orderby = "", $orderbyorder = "", $limit = "", $innerjoin = "") {
	global $CONFIG;
	global $query_count;
	global $mysql_errors;
	global $whmcsmysql;
	if (!$fields) {
		$fields = "*";
	}
	$query = "SELECT " . $fields . " FROM " . db_make_safe_field($table);
	if ($innerjoin) {
		$query .= " INNER JOIN " . db_escape_string($innerjoin) . "";
	}
	if ($where) {
		if (is_array($where)) {
			$criteria = array();
			foreach ($where as $origkey => $value) {
				$key = db_make_safe_field($origkey);
				if (is_array($value)) {
					if ($key == "default") {
						$key = "`default`";
					}
					if ($value['sqltype'] == "LIKE") {
						$criteria[] = "" . $key . " LIKE '%" . db_escape_string($value['value']) . "%'";
						continue;
					}
					if ($value['sqltype'] == "NEQ") {
						$criteria[] = "" . $key . "!='" . db_escape_string($value['value']) . "'";
						continue;
					}
					if ($value['sqltype'] == ">" && db_is_valid_amount($value['value'])) {
						$criteria[] = "" . $key . ">" . $value['value'];
						continue;
					}
					if ($value['sqltype'] == "<" && db_is_valid_amount($value['value'])) {
						$criteria[] = "" . $key . "<" . $value['value'];
						continue;
					}
					if ($value['sqltype'] == "<=" && db_is_valid_amount($value['value'])) {
						$criteria[] = "" . $origkey . "<=" . $value['value'];
						continue;
					}
					if ($value['sqltype'] == ">=" && db_is_valid_amount($value['value'])) {
						$criteria[] = "" . $origkey . ">=" . $value['value'];
						continue;
					}
					if ($value['sqltype'] == "TABLEJOIN") {
						$criteria[] = "" . $key . "=" . db_escape_string($value['value']) . "";
						continue;
					}
					if ($value['sqltype'] == "IN") {
						$criteria[] = "" . $key . " IN (" . db_build_in_array($value['values']) . ")";
						continue;
					}
					exit("Invalid input condition");
					continue;
				}
				if (substr($key, 0, 3) == "MD5") {
					$key = explode("(", $origkey, 2);
					$key = explode(")", $key[1], 2);
					$key = db_make_safe_field($key[0]);
					$key = "MD5(" . $key . ")";
				}
				else {
					$key = db_build_quoted_field($key);
				}
				$criteria[] = "" . $key . "='" . db_escape_string($value) . "'";
			}
			$query .= " WHERE " . implode(" AND ", $criteria);
		}
		else {
			$query .= " WHERE " . $where;
		}
	}
	if ($orderby) {
		$orderbysql = tokenizeOrderby($orderby, $orderbyorder);
		$query .= " ORDER BY " . implode(",", $orderbysql);
	}
	if ($limit) {
		if (strpos($limit, ",")) {
			$limit = explode(",", $limit);
			$limit = (int)$limit[0] . "," . (int)$limit[1];
		}
		else {
			$limit = (int)$limit;
		}
		$query .= " LIMIT " . $limit;
	}
	$result = mysql_query($query, $whmcsmysql);
	if (!$result && ($CONFIG['SQLErrorReporting'] || $mysql_errors)) {
		logActivity("SQL Error: " . mysql_error($whmcsmysql) . " - Full Query: " . $query);
	}
	++$query_count;
	return $result;
}
