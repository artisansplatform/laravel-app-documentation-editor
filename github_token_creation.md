# Github Documentation For Creating The Pull Request

- It is used when need to create a `Pull Request` from the laravel application. At that time we need to create the github token.

### Steps For Generating Fine-grained Token

1. First need to open the `Github Setting`
2. Then need to open the `Developer Settings`
    - Inside the Personal Access Tokens Open the Fine-grained Token
3. Now add the followings
    - Token Name
    - Description (If any)
    - Select Resource Owner
    - Expiration
    - Repository Access
        - Only Select Repositories
    - Permissions
        - Contents (Read and write)
        - Pull requests (Read and write)
4. Generate Token.
5. Save that Token.

**Note: **

Add that token in the .env file
