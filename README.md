# Minecraft Server Motd for PHP
我的世界服务器Motd协议封装和HTTP API实践

_尚未支持Java版_

## 🎬用法
请求地址: `/api/be.php`  
请求方式: `POST或GET`  
请求内容: `ip`  
端口不输入则默认是19132  
示例:  
`http://localhost:8003/api/be.php?ip=ntest.easecation.net:19132`  
返回  
1. 输入错误  
``` json
{
  "status": 400,
  "online": false,
  "message": "IP或端口输入无效"
}
```
2. 服务器离线  
``` json
{
  "status": 204,
  "online": false,
  "message": "服务器离线"
}
```
3. 服务器在线  
``` json
{
  "status": 200,
  "online": true,
  "host": "ntest.easecation.net",
  "ip": "42.186.64.243",
  "motd": "§l§aEase§6Cation§r§3 §r§7§kEC§r §l§cMURDER MYSTERY§r §7§kEC§r",
  "agreement": 503,
  "version": "1.18.30",
  "onlines": 4,
  "max": 5000,
  "level": "Powered by Nemisys",
  "gamemode": "Survival",
  "id": "275128025514481402",
  "delay": 58.177001953125
}
```
## 📖许可证
项目采用`Mozilla Public License Version 2.0`协议开源

二次修改源代码需要开源修改后的代码，对源代码修改之处需要提供说明文档
