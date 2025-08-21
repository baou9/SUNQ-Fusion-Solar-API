FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY . .
ENV NODE_ENV=production
USER node
EXPOSE 8081
CMD ["node", "server.js"]
