# Teradata transformation

Application which runs [KBC](https://connection.keboola.com/) transformations in Teradata DB

## Options

- `authorization` object (required): [workspace credentials](https://developers.keboola.com/extend/common-interface/folders/#exchanging-data-via-workspace)
- `parameters`
    - `blocks` array (required): list of blocks
        - `name` string (required): name of the block
        - `codes` array (required): list of codes
            - `name` string (required): name of the code
            - `script` array (required): list of sql queries

## Example Configuration

```json
{
  "authorization": {
    "workspace": {
      "host": "teradata_host",
      "user": "teradata_user",
      "password": "teradata_password",
      "database": "teradata_database",
      "schema": "teradata_schema"
    }
  },
  "parameters": {
    "blocks": [
      {
        "name": "first block",
        "codes": [
          {
            "name": "first code",
            "script": [
              "CREATE TABLE IF NOT EXISTS \"example\" (\"name\" VARCHAR(200),\"usercity\" VARCHAR(200));",
              "INSERT INTO \"example\" VALUES ('test example name', 'Prague'), ('test example name 2', 'Brno'), ('test example name 3', 'Ostrava')"
            ]
          }
        ]
      }
    ]
  }
}
```

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/teradata-transformation
cd teradata-transformation
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 

## License

MIT licensed, see [LICENSE](./LICENSE) file.
