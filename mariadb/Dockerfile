FROM mariadb:10.8
ENV MYSQL_ALLOW_EMPTY_PASSWORD no
ENV MYSQL_ROOT_PASSWORD rootpwd
# Important: Copy ALL SQL files
COPY ./*.sql /docker-entrypoint-initdb.d/ 
EXPOSE 3306