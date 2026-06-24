# Deployment files

Files created by the deploy helper script in this repo folder:

- `upload.txt` - WinSCP script that mirrors the local project to the FTP root using the provided credentials.

Usage (recommended):

1. Install WinSCP (https://winscp.net/) on your Windows machine.
2. Open a PowerShell prompt in the project folder and run:

```powershell
"C:\Program Files (x86)\WinSCP\WinSCP.com" /script=deploy\upload.txt /log=winscp-deploy.log
```

Notes:
- The `synchronize remote` command will make the remote folder match your local folder; it may delete remote files not present locally. Use with caution.
- If you prefer uploading a single zip, use `Compress-Archive` to create `aktly-deploy.zip` and upload it instead (then extract on server if possible).
- After upload, set permissions for `storage` and `bootstrap/cache` to be writable by the webserver.
