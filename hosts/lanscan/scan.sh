#you org token and address
server_token=c4ca4238a0b923820dcc509a6f75849b
server_address=ticket.smnik.ru

#scan with nmap
#nmap -sP -iL lan.list -oN output.txt > /dev/null

#send with curl
curl -F token=$server_token -F outfile=@output.txt https://$server_address/hosts/input/
