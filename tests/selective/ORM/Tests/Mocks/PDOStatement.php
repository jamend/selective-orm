<?php
namespace selective\ORM\Tests\Mocks;

class PDOStatement extends \PDOStatement
{
    /** @var PDO */
    public $pdo;
    public $sql;
    public $params;
    private $fakeData;

    private $affectedRows = 0;

    public function __construct($sql, PDO $pdo)
    {
        $this->sql = $sql;
        $this->pdo = $pdo;
        $this->fakeData = [
            'SHOW TABLES FROM `test` LIKE ?' => [
                '%' => [
                    0 =>
                        [
                            'Tables_in_sample' => 'Authors',
                        ],
                    1 =>
                        [
                            'Tables_in_sample' => 'Books',
                        ],
                ],
                'testprefix_%' => [
                    0 =>
                        [
                            'Tables_in_sample' => 'testprefix_Test',
                        ],
                ]
            ],
            'SHOW CREATE TABLE `Books`' => [
                '' => [
                    0 => [
                        'Table' => 'Books',
                        'Create Table' => 'CREATE TABLE `Books` (
  `idBook` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `idAuthor` int(11) NOT NULL,
  `isbn` varchar(32) NOT NULL,
  `description` text,
  `dateCreated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idBook`),
  KEY `idAuthor` (`idAuthor`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`idAuthor`) REFERENCES `authors` (`idAuthor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8'
                    ],
                ],
            ],
            'SHOW CREATE TABLE `Authors`' => [
                '' => [
                    0 => [
                        'Table' => 'Authors',
                        'Create Table' => 'CREATE TABLE `Authors` (
  `idAuthor` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`idAuthor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8'
                    ],
                ],
            ],
            'SHOW CREATE TABLE `testprefix_Test`' => [
                '' => [
                    0 => [
                        'Table' => 'testprefix_Test',
                        'Create Table' => 'CREATE TABLE `testprefix_Test` (
  `test` int(11) DEFAULT NULL,
  PRIMARY KEY (`test`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8'
                    ]
                ]
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
WHERE
	(`test`.`Books`.`idBook` = ?)' => [
                '1' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                ],
                '4' => [
                    0 => null
                ]
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`' => [
                '' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                    1 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '1',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                    ],
                    2 => [
                        0 => '3',
                        1 => 'My First Book',
                        2 => '2',
                        3 => '12345-6790',
                        4 => 'It was OK',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
WHERE
	(`test`.`Books`.`idAuthor` = ?)' => [
                '1' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                    1 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '1',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Authors`.`idAuthor`,
	`test`.`Authors`.`name`
FROM
	`test`.`Authors`
WHERE
	(`test`.`Authors`.`idAuthor` = ?)' => [
                '1' => [
                    0 => [
                        0 => '1',
                        1 => 'Author 1',
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated,
	`test`.`Authors`.`idAuthor`,
	`test`.`Authors`.`name`
FROM
	`test`.`Books`
	INNER JOIN `test`.`Authors`
		ON (`test`.`Books`.idAuthor = `test`.`Authors`.idAuthor)' => [
                '' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                        6 => '1',
                        7 => 'Author 1',
                    ],
                    1 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '1',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                        6 => '1',
                        7 => 'Author 1',
                    ],
                ],
            ],
            'SELECT
	`test`.`Authors`.`idAuthor`,
	`test`.`Authors`.`name`,
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Authors`
	LEFT JOIN `test`.`Books`
		ON (`test`.`Authors`.idAuthor = `test`.`Books`.idAuthor)' => [
                '' => [
                    0 => [
                        0 => '1',
                        1 => 'Author 1',
                        2 => '1',
                        3 => 'My First Book',
                        4 => '1',
                        5 => '12345-6789',
                        6 => 'It wasn\'t very good',
                        7 => time(),
                    ],
                    1 => [
                        0 => '1',
                        1 => 'Author 1',
                        2 => '2',
                        3 => 'My Second Book',
                        4 => '1',
                        5 => '12345-6790',
                        6 => 'It wasn\'t very good either',
                        7 => time(),
                    ],
                ],
            ],
            'SELECT title FROM Books' => [
                '' => [
                    0 => [
                        'title' => 'My First Book',
                    ],
                    1 => [
                        'title' => 'My Second Book',
                    ],
                    2 => [
                        'title' => 'My First Book',
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
WHERE
	(title LIKE ?)' => [
                '%First%' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                    1 => [
                        0 => '3',
                        1 => 'My First Book',
                        2 => '2',
                        3 => '12345-6789',
                        4 => 'It was OK',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
ORDER BY
	idBook ASC' => [
                '' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                    1 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '1',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                    ],
                    2 => [
                        0 => '3',
                        1 => 'My First Book',
                        2 => '2',
                        3 => '12345-6790',
                        4 => 'It was OK',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
ORDER BY
	idBook DESC' => [
                '' => [
                    0 => [
                        0 => '3',
                        1 => 'My First Book',
                        2 => '2',
                        3 => '12345-6790',
                        4 => 'It was OK',
                        5 => time(),
                    ],
                    1 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '1',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                    ],
                    2 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated
FROM
	`test`.`Books`
LIMIT 0, 1' => [
                '' => [
                    0 => [
                        0 => '1',
                        1 => 'My First Book',
                        2 => '1',
                        3 => '12345-6789',
                        4 => 'It wasn\'t very good',
                        5 => time(),
                    ],
                ],
            ],
            'SELECT
	`test`.`Books`.`idBook`,
	`test`.`Books`.`title`,
	`test`.`Books`.`idAuthor`,
	`test`.`Books`.`isbn`,
	`test`.`Books`.`description`,
	UNIX_TIMESTAMP(`test`.`Books`.`dateCreated`) AS dateCreated AS dateCreated
FROM
	`test`.`Books`
LIMIT 1, 1' => [
                '' => [
                    0 => [
                        0 => '2',
                        1 => 'My Second Book',
                        2 => '2',
                        3 => '12345-6790',
                        4 => 'It wasn\'t very good either',
                        5 => time(),
                    ],
                ],
            ],
        ];
    }

    public function execute($bound_input_params = null)
    {
        $this->params = $bound_input_params;
        $queryType = strtolower(substr($this->sql, 0, 6));
        if ($queryType === 'insert') {
            $this->pdo->lastInsertId = 3; // fake book
        }
        switch (strtolower(substr($this->sql, 0, 6))) {
            case 'insert':
            case 'update':
            case 'delete':
                $this->affectedRows = 1;
                break;
            case 'select':
                $this->affectedRows = 0;
                break;
        }
        return true;
    }

    public function rowCount()
    {
        return $this->affectedRows;
    }

    public function fetch($how = null, $orientation = null, $offset = null)
    {
        $params = implode(',', $this->params);
        if (!isset($this->fakeData[$this->sql][$params])) {
            throw new \Exception("Missing test data for query:\n" . $this->sql . "[{$params}]");
        }
        $row = current($this->fakeData[$this->sql][$params]);
        next($this->fakeData[$this->sql][$params]);
        return $row;
    }
}