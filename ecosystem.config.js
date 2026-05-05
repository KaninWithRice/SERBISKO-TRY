module.exports = {
  apps: [
    {
      name: 'ocr-server',
      script: 'python_services/ocr_server.py',
      interpreter: 'python3',
      args: '-u',
      restart_delay: 3000,
    },
    {
      name: 'lis-server',
      script: 'python_services/lis_server.py',
      interpreter: 'python3',
      args: '-u',
      restart_delay: 3000,
    },
    {
      name: 'enrollment-automation',
      script: 'python_services/enrollment_form_filler.py',
      interpreter: 'python3',
      args: '-u',
      restart_delay: 3000,
    },
    {
      name: 'arduino-server',
      script: 'scripts/arduino_server_fixed.py',
      interpreter: 'python3',
      args: '-u',
      restart_delay: 3000,
    },
    {
      name: 'firestore-sync',
      script: 'bridge/sync.js',
      cwd: 'bridge',
      restart_delay: 5000,
    }
  ]
};
