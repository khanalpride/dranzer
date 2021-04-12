export default {
  database: {
    types: {
      sqLite: 'SQLite is a relational database management system contained in a C library.'
          + ' In contrast to many other database management systems,'
          + ' SQLite is not a client–server database engine. Rather, it is embedded into the end program.',
      mySQL: 'MySQL is an open-source relational database management system.'
          + ' Its name is a combination of "My", the name of co-founder Michael Widenius\'s daughter, and "SQL",'
          + ' the abbreviation for Structured Query Language.',
      mongoDB: 'MongoDB is a cross-platform document-oriented database program.'
          + ' Classified as a NoSQL database program, MongoDB uses JSON-like documents with optional schemas.'
          + ' MongoDB is developed by MongoDB Inc. and licensed under the Server Side Public License.',
      postgreSQL: 'PostgreSQL, also known as Postgres, is a free and open-source relational database management system'
          + ' emphasizing extensibility and SQL compliance.'
          + ' It was originally named POSTGRES, referring to its origins as a successor to the Ingres database'
          + ' developed at the University of California, Berkeley.',
      sqlServer: 'Microsoft SQL Server is a relational database management system developed by Microsoft.'
          + ' As a database server, it is a software product with the primary function of storing and retrieving'
          + ' data as requested by other software applications—which may run either on the same computer or on another computer across a network.',
    },
    config: {
      dbPath: 'Location of the database file',
    },
  },
};
