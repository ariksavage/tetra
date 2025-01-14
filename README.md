# TETRA Project
## Installation

1. Clone the repository
1. Initialize git submodules: `git submodule update --init --recursive`
1. Configure *.ddev/config/yaml*
  - Set 
    - name: **you-project** (eg knowledge-direct)
    - host_https_port: **unique_port** (eg 20250)
    - host_db_port: **another_unique_port** (eg 20251)
1. Start DDEV: `ddev start`
1. Configure **proxy.conf.json**
```
{
  "/core/**/*": {
    "target": "https://127.0.0.1:{{host_https_port}}",
    "secure": false,
    "logLevel": "debug",
    "changeOrigin": true
  }
}
```

### First time setup
1. Run tetra installer to configure the database: `ddev tetra install`
### Migration from another system:
1. Run tetra db-migrate to configure the database: `ddev tetra db-migrate`